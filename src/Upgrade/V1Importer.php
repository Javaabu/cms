<?php

namespace Javaabu\Cms\Upgrade;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use RuntimeException;
use Throwable;

/**
 * Copies v1's content (in the cms_v1_* tables) into javaabu/post-types:
 *
 * | v1                                   | v2                                          |
 * | ------------------------------------ | ------------------------------------------- |
 * | posts of a post type                 | posts of the mapped post type               |
 * | Editor.js content (and translations) | block-editor documents                      |
 * | statuses                             | draft, pending, published (rejected, archived → draft unless the type has them) |
 * | document_no, expire_at, format, …    | meta of the post                            |
 * | category types and categories        | terms of the mapped taxonomy, nesting kept  |
 * | tags                                 | terms of the tags taxonomy                  |
 * | attachments of posts                 | moved to the new posts                      |
 *
 * Post ids are kept when the posts table is empty, so links to /posts/{id}
 * and attachments keep working.
 */
class V1Importer
{
    /** v1's feature columns, copied to meta when set */
    public const array META_COLUMNS = ['document_no', 'expire_at', 'format', 'video_url', 'page_style', 'ref_no', 'coords', 'city_id', 'recently_updated'];

    /** v1 post type slug => v2 post type key, used when the slugs differ */
    public const array DEFAULT_TYPES = ['pages' => 'page', 'posts' => 'news', 'blog' => 'news'];

    protected EditorJsConverter $converter;

    /** @var array<string, string> v1 post type => v2 post type */
    protected array $types = [];

    /** @var array<string, string> v1 category type => v2 taxonomy */
    protected array $taxonomies = [];

    /** @var array<int, int> v1 post id => v2 post id */
    protected array $post_ids = [];

    /** @var array<int, int> v1 category id => v2 term id */
    protected array $category_ids = [];

    /** @var array<int, int> v1 tag id => v2 term id */
    protected array $tag_ids = [];

    /** @var array<string, mixed> */
    protected array $report = [];

    /**
     * @param  array<string, string>  $types  v1 post type slug => v2 post type key
     * @param  array<string, string>  $taxonomies  v1 category type slug => v2 taxonomy key
     */
    public function __construct(array $types = [], array $taxonomies = [], ?EditorJsConverter $converter = null)
    {
        $this->types = $types;
        $this->taxonomies = $taxonomies;
        $this->converter = $converter ?? new EditorJsConverter;
    }

    /**
     * @return array{posts: int, terms: int, tags: int, attachments: int, meta: int, skipped_types: array<string, int>, skipped_blocks: array<string, int>, truncated: array<int, int>, kept_ids: bool}
     */
    public function import(bool $dry_run = false): array
    {
        if (! V1Tables::hasRenamed()) {
            throw new RuntimeException('No v1 content found: the ' . V1Tables::renamed('posts') . ' table is missing. Run php artisan migrate first.');
        }

        $this->report = ['posts' => 0, 'terms' => 0, 'tags' => 0, 'attachments' => 0, 'meta' => 0, 'skipped_types' => [], 'skipped_blocks' => [], 'truncated' => [], 'kept_ids' => false];

        $connection = $this->db();

        $connection->beginTransaction();

        try {
            $this->importCategories();
            $this->importTags();
            $this->importPosts();
            $this->importPostCategories();
            $this->importPostTags();
            $this->moveAttachments();

            $dry_run ? $connection->rollBack() : $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        $this->report['skipped_blocks'] = $this->converter->skipped();

        return $this->report;
    }

    /**
     * v1's post types, with how many posts each has and the v2 type they map to
     *
     * @return array<int, array{slug: string, name: string, singular_name: string, icon: string|null, posts: int, maps_to: string|null}>
     */
    public function postTypes(): array
    {
        $table = V1Tables::renamed('post_types');
        $counts = $this->db()->table(V1Tables::renamed('posts'))->selectRaw('type, count(*) as aggregate')->groupBy('type')->pluck('aggregate', 'type');

        $types = Schema::hasTable($table)
            ? $this->db()->table($table)->orderBy('order_column')->get()->map(fn ($type) => (array) $type)->all()
            : [];

        $known = array_column($types, 'slug');

        foreach ($counts as $slug => $count) {
            if (! in_array($slug, $known, true)) {
                $types[] = ['slug' => $slug, 'name' => Str::headline($slug), 'singular_name' => Str::headline(Str::singular($slug)), 'icon' => null];
            }
        }

        return array_map(fn (array $type) => [
            'slug' => (string) $type['slug'],
            'name' => (string) $type['name'],
            'singular_name' => (string) ($type['singular_name'] ?? $type['name']),
            'icon' => $type['icon'] ?? null,
            'posts' => (int) ($counts[$type['slug']] ?? 0),
            'maps_to' => $this->targetType((string) $type['slug']),
        ], $types);
    }

    /**
     * The v2 post type a v1 type's posts go to, or null to leave them out
     */
    public function targetType(string $slug): ?string
    {
        $candidates = [$this->types[$slug] ?? null, $slug, Str::singular($slug), static::DEFAULT_TYPES[$slug] ?? null];

        foreach (array_filter($candidates) as $key) {
            if (PostTypes::has($key)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The v2 taxonomy a v1 category type's categories go to
     */
    public function targetTaxonomy(string $slug): ?string
    {
        $candidates = [$this->taxonomies[$slug] ?? null, $slug, Str::snake($slug), 'categories'];

        foreach (array_filter($candidates) as $key) {
            if (PostTypes::findTaxonomy($key)) {
                return $key;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Terms
    |--------------------------------------------------------------------------
    */

    protected function importCategories(): void
    {
        $categories = V1Tables::renamed('categories');
        $types = V1Tables::renamed('category_types');

        if (! Schema::hasTable($categories)) {
            return;
        }

        $type_slugs = Schema::hasTable($types) ? $this->db()->table($types)->pluck('slug', 'id')->all() : [];

        // Parents first: nested set order
        $rows = $this->db()->table($categories)->orderBy(Schema::hasColumn($categories, '_lft') ? '_lft' : 'id')->get();

        foreach ($rows as $row) {
            $taxonomy = $this->targetTaxonomy((string) ($type_slugs[$row->type_id] ?? 'categories'));

            if (! $taxonomy) {
                continue;
            }

            $parent = $row->parent_id ?? null;

            $this->category_ids[$row->id] = $this->insertTerm($taxonomy, $row, [
                'parent_id' => $parent && isset($this->category_ids[$parent]) ? $this->category_ids[$parent] : null,
                'order' => (int) ($row->order_column ?? 0),
            ]);

            $this->report['terms']++;
        }
    }

    protected function importTags(): void
    {
        $tags = V1Tables::renamed('tags');

        if (! Schema::hasTable($tags) || ! PostTypes::findTaxonomy('tags')) {
            return;
        }

        foreach ($this->db()->table($tags)->orderBy('id')->get() as $row) {
            $this->tag_ids[$row->id] = $this->insertTerm('tags', $row, ['slug' => Str::slug($row->name) ?: (string) $row->id]);
            $this->report['tags']++;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function insertTerm(string $taxonomy, object $row, array $attributes): int
    {
        $table = config('post-types.tables.terms', 'post_terms');
        $slug = $this->uniqueSlug($table, ['taxonomy' => $taxonomy], (string) ($attributes['slug'] ?? $row->slug ?? Str::slug($row->name)));

        return (int) $this->db()->table($table)->insertGetId([
            'taxonomy' => $taxonomy,
            'name' => Str::limit((string) $row->name, 255, ''),
            'slug' => $slug,
            'description' => null,
            'parent_id' => $attributes['parent_id'] ?? null,
            'order' => $attributes['order'] ?? 0,
            'translations' => $this->translations($row, []),
            'lang' => $row->lang ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Posts
    |--------------------------------------------------------------------------
    */

    protected function importPosts(): void
    {
        $table = config('post-types.tables.posts', 'posts');
        $keep_ids = ! $this->db()->table($table)->exists();
        $this->report['kept_ids'] = $keep_ids;

        $rows = $this->db()->table(V1Tables::renamed('posts'))->orderBy('id')->get();

        foreach ($rows as $row) {
            $type = $this->targetType((string) $row->type);

            if (! $type) {
                $this->report['skipped_types'][$row->type] = ($this->report['skipped_types'][$row->type] ?? 0) + 1;

                continue;
            }

            $title = (string) $row->title;

            if (mb_strlen($title) > 255) {
                $this->report['truncated'][] = (int) $row->id;
                $title = mb_substr($title, 0, 255);
            }

            $values = [
                'type' => $type,
                'title' => $title,
                'slug' => $this->uniqueSlug($table, ['type' => $type], (string) $row->slug),
                'excerpt' => $row->excerpt ?? null,
                'content' => $this->content($row->content ?? null),
                'status' => $this->status((string) $row->status, $type),
                'published_at' => $row->published_at ?? null,
                'translations' => $this->translations($row, ['content']),
                'lang' => $row->lang ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
                'deleted_at' => $row->deleted_at ?? null,
            ];

            if ($keep_ids) {
                $values['id'] = $row->id;
            }

            $id = (int) $this->db()->table($table)->insertGetId($values);

            $this->post_ids[$row->id] = $id;
            $this->report['posts']++;

            $this->importMeta($id, $row);
        }
    }

    protected function importMeta(int $id, object $row): void
    {
        $meta = [];

        foreach (static::META_COLUMNS as $column) {
            $value = $row->{$column} ?? null;

            if ($value !== null && $value !== '' && $value !== 0 && $value !== '0') {
                $meta[$column] = $value;
            }
        }

        if ($meta === []) {
            return;
        }

        $model = PostTypes::postModel();

        /** @var Post|null $post */
        $post = $model::withTrashed()->find($id);

        $post?->setMeta($meta);

        $this->report['meta'] += count($meta);
    }

    protected function importPostCategories(): void
    {
        $this->importPivot(V1Tables::renamed('category_model'), 'category_id', $this->category_ids);
    }

    protected function importPostTags(): void
    {
        $this->importPivot(V1Tables::renamed('tag_model'), 'tag_id', $this->tag_ids);
    }

    /**
     * @param  array<int, int>  $term_ids
     */
    protected function importPivot(string $table, string $column, array $term_ids): void
    {
        if (! Schema::hasTable($table) || $term_ids === []) {
            return;
        }

        $pivot = config('post-types.tables.post_term', 'post_term');
        $rows = [];

        foreach ($this->db()->table($table)->get() as $row) {
            $post = $this->post_ids[$row->model_id] ?? null;
            $term = $term_ids[$row->{$column}] ?? null;

            if ($post && $term && $this->isV1PostMorph((string) $row->model_type)) {
                $rows["{$post}-{$term}"] = ['post_id' => $post, 'term_id' => $term];
            }
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            $this->db()->table($pivot)->insert($chunk);
        }
    }

    /**
     * Point v1 posts' attachments (javaabu/mediapicker) at the new posts
     */
    protected function moveAttachments(): void
    {
        if (! Schema::hasTable('attachments') || $this->post_ids === []) {
            return;
        }

        $model = PostTypes::postModel();
        $morph = (new $model)->getMorphClass();

        $attachments = $this->db()->table('attachments')
            ->whereIn('model_type', ['post', 'Javaabu\\Cms\\Models\\Post', 'Javaabu\\Cms\\Models\\TranslatablePost'])
            ->get(['id', 'model_id']);

        foreach ($attachments as $attachment) {
            if (! isset($this->post_ids[$attachment->model_id])) {
                continue;
            }

            $this->db()->table('attachments')->where('id', $attachment->id)->update([
                'model_type' => $morph,
                'model_id' => $this->post_ids[$attachment->model_id],
            ]);

            $this->report['attachments']++;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Values
    |--------------------------------------------------------------------------
    */

    protected function content(mixed $content): ?string
    {
        $document = $this->converter->convert($content);

        return $document === null ? null : json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function status(string $status, string $type): string
    {
        $definition = PostTypes::get($type);

        if ($status === 'scheduled') {
            return 'published';
        }

        if ($definition->hasStatus($status)) {
            return $status;
        }

        return match ($status) {
            'published' => 'published',
            'pending' => $definition->hasStatus('pending') ? 'pending' : $definition->getDefaultStatus(),
            default => $definition->hasStatus('draft') ? 'draft' : $definition->getDefaultStatus(),
        };
    }

    /**
     * v1's JSON translations (javaabu/translatable), with Editor.js fields converted
     *
     * @param  array<int, string>  $content_fields
     */
    protected function translations(object $row, array $content_fields): ?string
    {
        $translations = $row->translations ?? null;

        if ($translations === null || $translations === '') {
            return null;
        }

        $decoded = is_string($translations) ? json_decode($translations, true) : (array) $translations;

        if (! is_array($decoded) || $decoded === []) {
            return null;
        }

        foreach ($decoded as $locale => $fields) {
            if (! is_array($fields)) {
                continue;
            }

            foreach ($content_fields as $field) {
                if (array_key_exists($field, $fields)) {
                    $document = $this->converter->convert($fields[$field]);
                    $decoded[$locale][$field] = $document;
                }
            }
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<string, string>  $scope
     */
    protected function uniqueSlug(string $table, array $scope, string $slug): string
    {
        $slug = Str::limit($slug !== '' ? $slug : Str::random(8), 180, '');
        $candidate = $slug;
        $suffix = 2;

        while ($this->db()->table($table)->where($scope)->where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    protected function isV1PostMorph(string $type): bool
    {
        return in_array($type, ['post', 'Javaabu\\Cms\\Models\\Post', 'Javaabu\\Cms\\Models\\TranslatablePost'], true);
    }

    protected function db(): Connection
    {
        return DB::connection();
    }
}
