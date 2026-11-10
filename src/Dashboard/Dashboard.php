<?php

namespace Javaabu\Cms\Dashboard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;

/**
 * What the admin dashboard shows a user: counts per post type they can view
 * and the posts edited most recently. Also the subject of the dashboard
 * policy: Gate::allows('view', Dashboard::class).
 */
class Dashboard
{
    public function __construct(
        protected ?Model $user,
        protected string $prefix = '',
    ) {}

    public static function for(?Model $user, string $prefix = ''): static
    {
        return new static($user, $prefix);
    }

    /**
     * @return array{post_types: array<int, array<string, mixed>>, recent: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'post_types' => $this->postTypes(),
            'recent' => $this->recent((int) config('cms.dashboard.recent', 5)),
        ];
    }

    /**
     * The post types the user can view, with counts and links
     *
     * @return array<int, array{key: string, label: string, singular_label: string, icon: string, counts: array<string, int>, index_url: string|null, create_url: string|null}>
     */
    public function postTypes(): array
    {
        return array_values(array_map(fn (PostType $type) => [
            'key' => $type->getKey(),
            'label' => $type->getPluralLabel(),
            'singular_label' => $type->getSingularLabel(),
            'icon' => $type->getIcon(),
            'counts' => $this->counts($type),
            'index_url' => $this->url($type->getKey() . '.index'),
            'create_url' => $this->gate()?->allows('create', $type) ? $this->url($type->getKey() . '.create') : null,
        ], $this->visibleTypes()));
    }

    /**
     * Published, scheduled, draft and pending posts of a type
     *
     * @return array{published: int, scheduled: int, draft: int, pending: int}
     */
    public function counts(PostType $type): array
    {
        return [
            'published' => $type->query()->published()->count(),
            'scheduled' => $type->query()->scheduled()->count(),
            'draft' => $type->query()->withStatus('draft')->count(),
            'pending' => $type->query()->withStatus('pending')->count(),
        ];
    }

    /**
     * The posts edited most recently, of the post types the user can view
     *
     * @return array<int, array{id: int|string, title: string, post_type: string, post_type_label: string, status: string, status_color: string, updated_at: string|null, edit_url: string|null}>
     */
    public function recent(int $limit = 5): array
    {
        $types = $this->visibleTypes();

        if ($types === [] || $limit < 1) {
            return [];
        }

        $model = PostTypes::postModel();

        /** @var iterable<Post> $posts */
        $posts = $model::query()
            ->whereIn('type', array_keys($types))
            ->latest('updated_at')
            ->latest((new $model)->getKeyName())
            ->limit($limit)
            ->get();

        $recent = [];

        foreach ($posts as $post) {
            $type = $types[$post->type];

            $recent[] = [
                'id' => $post->getKey(),
                'title' => (string) $post->title,
                'post_type' => $type->getKey(),
                'post_type_label' => $type->getSingularLabel(),
                'status' => $post->displayStatus(),
                'status_color' => $post->displayStatusColor(),
                'updated_at' => $post->updated_at?->toIso8601String(),
                'edit_url' => $this->gate()?->allows('update', $post) ? $this->url($type->getKey() . '.edit', ['post' => $post->getKey()]) : null,
            ];
        }

        return $recent;
    }

    /**
     * @return array<string, PostType>
     */
    protected function visibleTypes(): array
    {
        $gate = $this->gate();

        if (! $gate) {
            return [];
        }

        return array_filter(PostTypes::types(), fn (PostType $type) => $gate->allows('viewAny', $type));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function url(string $name, array $parameters = []): ?string
    {
        $name = $this->prefix . $name;

        return Route::has($name) ? route($name, $parameters) : null;
    }

    protected function gate(): ?\Illuminate\Contracts\Auth\Access\Gate
    {
        return $this->user ? Gate::forUser($this->user) : null;
    }
}
