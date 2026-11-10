<?php

namespace Javaabu\Cms\Widgets;

use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Widget;

/**
 * The latest published posts of a post type, with a link to its archive
 */
class RecentPostsWidget extends Widget
{
    public static function key(): string
    {
        return 'recent-posts';
    }

    public function name(): string
    {
        return __('Recent posts');
    }

    public function description(): ?string
    {
        return __('The latest posts of a post type, newest first.');
    }

    public function icon(): ?string
    {
        return 'newspaper';
    }

    public function settings(): array
    {
        return [
            TextInput::make('title')->label(__('Title'))->maxLength(255)->translatable(),
            Select::make('post_type')
                ->label(__('Post type'))
                ->options(fn () => static::postTypeOptions())
                ->default(config('cms.home.post_type', 'news'))
                ->required(),
            TextInput::make('count')
                ->label(__('Number of posts'))
                ->integer()
                ->minValue(1)
                ->maxValue(20)
                ->default(5)
                ->required(),
            Checkbox::make('show_date')->label(__('Show the date'))->default(true),
        ];
    }

    public function shouldRender(WidgetInstance $widget): bool
    {
        return static::postType($widget) !== null;
    }

    public function data(WidgetInstance $widget): array
    {
        $type = static::postType($widget);

        if (! $type) {
            return ['posts' => [], 'archive_url' => null];
        }

        $count = max(1, min(20, (int) ($widget->setting('count') ?: 5)));

        $posts = $type->query()
            ->published()
            ->latestPublished()
            ->limit($count)
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->getKey(),
                'title' => (string) $post->title,
                'url' => $post->permalink(),
                'date' => $post->published_at?->toIso8601String(),
                'date_label' => $post->published_at?->translatedFormat('j F Y'),
            ])
            ->all();

        return [
            'posts' => $posts,
            'archive_url' => $type->archiveUrl(),
            'archive_label' => __('All :type', ['type' => mb_strtolower($type->getPluralLabel())]),
        ];
    }

    public function view(): string
    {
        return 'cms::widgets.recent-posts';
    }

    /**
     * The public post types, for the select
     *
     * @return array<string, string>
     */
    public static function postTypeOptions(): array
    {
        $options = [];

        foreach (PostTypes::types() as $key => $type) {
            if ($type->isPublic()) {
                $options[$key] = $type->getPluralLabel();
            }
        }

        return $options;
    }

    protected static function postType(WidgetInstance $widget): ?PostType
    {
        $type = PostTypes::find((string) $widget->setting('post_type'));

        return $type && $type->isPublic() ? $type : null;
    }
}
