<?php

namespace Javaabu\Cms\Navigation;

use Javaabu\Cms\Dashboard\Dashboard;
use Javaabu\DynamicMenubuilder\DynamicMenubuilder;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Widgets\WidgetLocation;

/**
 * The CMS's own links: the dashboard, a link per post type and taxonomy, and
 * the media library, menus, widgets, settings and roles. Each is shown only
 * when its route is registered and the user may view it.
 */
class DefaultNavigation
{
    public static function register(Navigation $navigation): void
    {
        $navigation
            ->group('content', fn () => __('Content'), 10)
            ->group('appearance', fn () => __('Appearance'), 20)
            ->group('administration', fn () => __('Administration'), 30);

        $navigation->add(
            NavigationItem::make('dashboard')
                ->label(fn () => __('Dashboard'))
                ->route('dashboard')
                ->icon('layout-dashboard')
                ->order(0)
                ->can('view', Dashboard::class),
        );

        $navigation->provide(fn () => static::contentItems());

        $navigation->add(
            NavigationItem::make('media')
                ->label(fn () => __('Media'))
                ->route('media.index')
                ->icon('images')
                ->group('content')
                ->order(900)
                ->can('viewAny', fn () => Mediapicker::mediaModel()),
            NavigationItem::make('menus')
                ->label(fn () => __('Menus'))
                ->route('menus.index')
                ->icon('menu')
                ->group('appearance')
                ->order(10)
                ->can('viewAny', fn () => DynamicMenubuilder::menuModel()),
            NavigationItem::make('widgets')
                ->label(fn () => __('Widgets'))
                ->route('widgets.index')
                ->icon('layout-panel-left')
                ->group('appearance')
                ->order(20)
                ->can('viewAny', WidgetLocation::class),
            NavigationItem::make('settings')
                ->label(fn () => __('Settings'))
                ->route('settings.index')
                ->icon('settings')
                ->group('administration')
                ->order(10)
                ->can('viewAny', SettingsPage::class),
            NavigationItem::make('roles')
                ->label(fn () => __('Roles'))
                ->route('roles.index')
                ->icon('shield-check')
                ->group('administration')
                ->order(20)
                ->can('viewAny', fn () => config('permission.models.role')),
        );
    }

    /**
     * A link per post type, then a link per taxonomy, in their registered order
     *
     * @return array<int, NavigationItem>
     */
    public static function contentItems(): array
    {
        $items = [];
        $order = 100;

        foreach (PostTypes::types() as $type) {
            $items[] = static::postTypeItem($type, $order++);
        }

        $order = 500;

        foreach (PostTypes::taxonomies() as $taxonomy) {
            $items[] = static::taxonomyItem($taxonomy, $order++);
        }

        return $items;
    }

    public static function postTypeItem(PostType $type, int $order = 100): NavigationItem
    {
        return NavigationItem::make('post-type-' . $type->getKey())
            ->label(fn () => $type->getPluralLabel())
            ->route($type->getKey() . '.index')
            ->icon($type->getIcon())
            ->group('content')
            ->order($order)
            ->can('viewAny', $type);
    }

    public static function taxonomyItem(Taxonomy $taxonomy, int $order = 500): NavigationItem
    {
        return NavigationItem::make('taxonomy-' . $taxonomy->getKey())
            ->label(fn () => $taxonomy->getPluralLabel())
            ->route('taxonomies.' . $taxonomy->getKey() . '.index')
            ->icon($taxonomy->getIcon())
            ->group('content')
            ->order($order)
            ->can('viewAny', $taxonomy);
    }
}
