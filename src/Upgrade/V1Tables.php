<?php

namespace Javaabu\Cms\Upgrade;

use Illuminate\Support\Facades\Schema;

/**
 * v1's tables: finding them, and renaming them out of javaabu/post-types' way
 */
class V1Tables
{
    /** v1's tables (by their default names), in the order they can be renamed */
    public const array TABLES = ['tag_model', 'tags', 'category_model', 'categories', 'category_types', 'post_types', 'posts'];

    /**
     * Whether `posts` is v1's table (it has v1's menu_order and no author morph)
     */
    public static function isV1PostsTable(string $table = 'posts'): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'menu_order')
            && ! Schema::hasColumn($table, 'author_type');
    }

    /**
     * The renamed name of a v1 table: cms_v1_posts
     */
    public static function renamed(string $table): string
    {
        return config('cms.upgrade.prefix', 'cms_v1_') . $table;
    }

    /**
     * Rename v1's tables when the v1 posts table is there
     *
     * @return array<string, string> old name => new name
     */
    public static function rename(): array
    {
        if (! static::isV1PostsTable()) {
            return [];
        }

        $renamed = [];

        foreach (static::TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasTable(static::renamed($table))) {
                Schema::rename($table, static::renamed($table));
                $renamed[$table] = static::renamed($table);
            }
        }

        return $renamed;
    }

    /**
     * Whether v1's renamed tables are there, to import
     */
    public static function hasRenamed(): bool
    {
        return Schema::hasTable(static::renamed('posts'));
    }
}
