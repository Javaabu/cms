<?php

namespace Javaabu\Cms\Console;

use DB;
use Illuminate\Console\Command;
use Javaabu\Cms\Upgrade\V1Importer;
use Javaabu\Cms\Upgrade\V1Tables;
use Throwable;

/**
 * Brings javaabu/cms v1's content (kept in the cms_v1_* tables by the
 * rename migration) into javaabu/post-types. See docs/upgrading.md.
 */
class ImportV1Command extends Command
{
    protected $signature = 'cms:import-v1
        {--type=* : Map a v1 post type to a v2 one, v1:v2 (downloads:document); repeatable}
        {--taxonomy=* : Map a v1 category type to a v2 taxonomy, v1:v2 (news-categories:categories); repeatable}
        {--dry-run : Import inside a transaction that is rolled back, to see what would happen}
        {--force : Import even though the posts table already has posts}';

    protected $description = 'Import the posts, categories, tags and attachments of javaabu/cms v1';

    public function handle(): int
    {
        if (! V1Tables::hasRenamed()) {
            $this->components->error(__('No v1 content found. Run php artisan migrate first: it renames v1\'s tables to :table.', ['table' => V1Tables::renamed('{table}')]));

            return self::FAILURE;
        }

        $importer = new V1Importer($this->pairs('type'), $this->pairs('taxonomy'));

        $this->showTypes($importer);

        $posts = config('post-types.tables.posts', 'posts');

        if (! $this->option('force') && ! $this->option('dry-run') && DB::table($posts)->exists()) {
            $this->components->error(__('The :table table already has posts. Pass --force to import anyway (v1 ids won\'t be kept).', ['table' => $posts]));

            return self::FAILURE;
        }

        try {
            $report = $importer->import((bool) $this->option('dry-run'));
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail(__('Posts'), (string) $report['posts']);
        $this->components->twoColumnDetail(__('Categories'), (string) $report['terms']);
        $this->components->twoColumnDetail(__('Tags'), (string) $report['tags']);
        $this->components->twoColumnDetail(__('Meta values'), (string) $report['meta']);
        $this->components->twoColumnDetail(__('Attachments moved'), (string) $report['attachments']);
        $this->components->twoColumnDetail(__('Post ids kept'), $report['kept_ids'] ? __('yes') : __('no'));

        foreach ($report['skipped_types'] as $type => $count) {
            $this->components->warn(__('Left out :count posts of the v1 post type :type: register a post type for them, or map it with --type=:type:{key}.', ['count' => $count, 'type' => $type]));
        }

        foreach ($report['skipped_blocks'] as $block => $count) {
            $this->components->warn(__('Left out :count ":block" content blocks, which v2 has no block for.', ['count' => $count, 'block' => $block]));
        }

        if ($report['truncated'] !== []) {
            $this->components->warn(__('Titles longer than 255 characters were shortened (v1 posts :ids).', ['ids' => implode(', ', $report['truncated'])]));
        }

        if ($this->option('dry-run')) {
            $this->components->info(__('Dry run: nothing was saved.'));
        } else {
            $this->components->info(__('Imported. The v1 tables are still there; drop them once you\'ve checked the content.'));
        }

        return self::SUCCESS;
    }

    protected function showTypes(V1Importer $importer): void
    {
        $rows = array_map(fn (array $type) => [
            $type['slug'],
            $type['name'],
            $type['posts'],
            $type['maps_to'] ?? '—',
        ], $importer->postTypes());

        $this->table([__('v1 post type'), __('Name'), __('Posts'), __('v2 post type')], $rows);
    }

    /**
     * @return array<string, string>
     */
    protected function pairs(string $option): array
    {
        $pairs = [];

        foreach ((array) $this->option($option) as $pair) {
            [$from, $to] = array_pad(explode(':', (string) $pair, 2), 2, '');

            if ($from !== '' && $to !== '') {
                $pairs[$from] = $to;
            }
        }

        return $pairs;
    }
}
