<?php

namespace Javaabu\Cms\Content;

use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;

/**
 * The definitions the CMS registers by default (config('cms.post_types'),
 * config('cms.taxonomies')). Start from these to register a changed version:
 *
 *     PostTypes::register(Defaults::news()->permalink('{slug}'));
 */
class Defaults
{
    public static function page(): PostType
    {
        return PostType::make('page')
            ->labels('Page', 'Pages')
            ->description('Pages of the site, at the site root: /about')
            ->icon('file-text')
            ->supports([PostType::CONTENT, PostType::MEDIA, PostType::AUTHOR])
            ->slug('')
            ->permalink('{slug}')
            ->archive(false)
            ->order(10);
    }

    public static function news(): PostType
    {
        return PostType::make('news')
            ->labels('News article', 'News')
            ->description('News articles, listed newest first')
            ->icon('newspaper')
            ->supports([PostType::CONTENT, PostType::EXCERPT, PostType::MEDIA, PostType::AUTHOR, PostType::SCHEDULING])
            ->taxonomies(['categories', 'tags'])
            ->slug('news')
            ->permalink('{year}/{month}/{slug}')
            ->order(20);
    }

    public static function categories(): Taxonomy
    {
        return Taxonomy::make('categories')
            ->labels('Category', 'Categories')
            ->icon('folder-tree')
            ->hierarchical();
    }

    public static function tags(): Taxonomy
    {
        return Taxonomy::make('tags')
            ->labels('Tag', 'Tags')
            ->icon('tags');
    }

    /**
     * The default post type with this key
     */
    public static function postType(string $key): ?PostType
    {
        return match ($key) {
            'page' => static::page(),
            'news' => static::news(),
            default => null,
        };
    }

    /**
     * The default taxonomy with this key
     */
    public static function taxonomy(string $key): ?Taxonomy
    {
        return match ($key) {
            'categories' => static::categories(),
            'tags' => static::tags(),
            default => null,
        };
    }
}
