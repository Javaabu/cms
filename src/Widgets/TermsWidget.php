<?php

namespace Javaabu\Cms\Widgets;

use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Widget;

/**
 * The terms of a taxonomy (categories, tags), linking to their archives on
 * a post type, with how many published posts each has
 */
class TermsWidget extends Widget
{
    public static function key(): string
    {
        return 'terms';
    }

    public function name(): string
    {
        return __('Categories and tags');
    }

    public function description(): ?string
    {
        return __('The categories or tags of a post type, linking to their posts.');
    }

    public function icon(): ?string
    {
        return 'folder-tree';
    }

    public function settings(): array
    {
        return [
            TextInput::make('title')->label(__('Title'))->maxLength(255)->translatable(),
            Select::make('source')
                ->label(__('Terms'))
                ->help(__('A taxonomy of a post type. Links go to that post type\'s posts.'))
                ->options(fn () => static::sourceOptions())
                ->default('news:categories')
                ->required(),
            Checkbox::make('show_counts')->label(__('Show how many posts each has'))->default(false),
            Checkbox::make('hide_empty')->label(__('Hide terms without posts'))->default(true),
        ];
    }

    public function shouldRender(WidgetInstance $widget): bool
    {
        return static::source($widget) !== null;
    }

    public function data(WidgetInstance $widget): array
    {
        $source = static::source($widget);

        if (! $source) {
            return ['terms' => []];
        }

        [$type, $taxonomy] = $source;

        $model = PostTypes::termModel();

        $terms = $model::query()
            ->where('taxonomy', $taxonomy->getKey())
            ->withCount(['posts as posts_count' => fn ($query) => $query->ofType($type->getKey())->published()])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        if ($widget->setting('hide_empty', true)) {
            $terms = $terms->filter(fn (Term $term) => $term->posts_count > 0);
        }

        return [
            'terms' => $terms->map(fn (Term $term) => [
                'id' => $term->getKey(),
                'name' => (string) $term->name,
                'url' => $term->archiveUrl($type),
                'count' => (int) $term->posts_count,
                'parent_id' => $term->parent_id,
            ])->values()->all(),
            'show_counts' => (bool) $widget->setting('show_counts'),
        ];
    }

    public function view(): string
    {
        return 'cms::widgets.terms';
    }

    /**
     * `{post type}:{taxonomy}` for every public post type and its taxonomies
     *
     * @return array<string, string>
     */
    public static function sourceOptions(): array
    {
        $options = [];

        foreach (PostTypes::types() as $type) {
            if (! $type->isPublic()) {
                continue;
            }

            foreach ($type->getTaxonomies() as $taxonomy) {
                $options[$type->getKey() . ':' . $taxonomy->getKey()] = $type->getPluralLabel() . ' › ' . $taxonomy->getPluralLabel();
            }
        }

        return $options;
    }

    /**
     * @return array{0: PostType, 1: Taxonomy}|null
     */
    protected static function source(WidgetInstance $widget): ?array
    {
        [$type, $taxonomy] = array_pad(explode(':', (string) $widget->setting('source'), 2), 2, '');

        $type = PostTypes::find($type);

        if (! $type || ! $type->isPublic() || ! $type->hasTaxonomy($taxonomy)) {
            return null;
        }

        return [$type, PostTypes::getTaxonomy($taxonomy)];
    }
}
