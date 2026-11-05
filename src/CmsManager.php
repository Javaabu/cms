<?php

namespace Javaabu\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Javaabu\Cms\Navigation\DefaultNavigation;
use Javaabu\Cms\Navigation\Navigation;
use Javaabu\Cms\Routing\CmsRoutes;
use Javaabu\Cms\Support\Translations;
use Javaabu\DynamicMenubuilder\Facades\Menus;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Widgets\Facades\Widgets;
use Throwable;

/**
 * The CMS: its admin navigation, the site's details from the "Site"
 * settings page, and what the theme shares with Blade and React. Use it
 * through the Cms facade.
 */
class CmsManager
{
    protected ?Navigation $navigation = null;

    /**
     * The admin navigation. Add your own links to it in a service provider:
     * Cms::navigation()->add(NavigationItem::make('reports')->…)
     */
    public function navigation(): Navigation
    {
        if (! $this->navigation) {
            $this->navigation = new Navigation;

            DefaultNavigation::register($this->navigation);
        }

        return $this->navigation;
    }

    /**
     * The navigation for a user, resolved against the admin routes of the
     * current request (or the first Route::cmsAdmin() call)
     *
     * @return array<int, array<string, mixed>>
     */
    public function navigationFor(?Model $user, ?Request $request = null): array
    {
        $request ??= request();

        return $this->navigation()->resolve(
            $user,
            CmsRoutes::adminName($request),
            $request->route()?->getName(),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Site details
    |--------------------------------------------------------------------------
    */

    public function siteName(): string
    {
        $name = $this->setting('cms_site_name');

        return is_string($name) && $name !== '' ? $name : (string) config('app.name');
    }

    public function tagline(): ?string
    {
        $tagline = $this->setting('cms_tagline');

        return is_string($tagline) && $tagline !== '' ? $tagline : null;
    }

    public function footerText(): ?string
    {
        $text = $this->setting('cms_footer_text');

        return is_string($text) && $text !== '' ? $text : null;
    }

    /**
     * The published page chosen to show on the home page, if any
     */
    public function frontPage(): ?Post
    {
        $id = $this->setting('cms_front_page');
        $type = PostTypes::find('page');

        if (! $id || ! $type) {
            return null;
        }

        return $type->query()->published()->find($id);
    }

    /**
     * The post type whose latest posts the home page lists
     */
    public function homePostType(): ?PostType
    {
        $key = $this->setting('cms_home_post_type') ?: config('cms.home.post_type', 'news');
        $type = PostTypes::find((string) $key);

        return $type && $type->isPublic() ? $type : null;
    }

    /**
     * What the theme needs about the site, for Blade and React
     *
     * @return array{name: string, tagline: string|null, footer_text: string|null, home_url: string, locale: string, direction: string, locales: array<int, array<string, mixed>>}
     */
    public function site(): array
    {
        return [
            'name' => $this->siteName(),
            'tagline' => $this->tagline(),
            'footer_text' => $this->footerText(),
            'home_url' => CmsRoutes::homeUrl() ?? url('/'),
            'locale' => Translations::currentLocale(),
            'direction' => Translations::direction(),
            'locales' => Translations::switcher(),
        ];
    }

    /**
     * The menus of the CMS's menu locations, as javaabu/dynamic-menubuilder
     * shares them with React (`menus.header`)
     *
     * @return array<string, mixed>
     */
    public function menus(): array
    {
        $locations = array_values(array_filter(
            array_keys((array) config('cms.menu_locations', [])),
            fn ($location) => Menus::hasLocation((string) $location),
        ));

        return $locations === [] ? [] : Menus::shared($locations);
    }

    /**
     * The widgets of the CMS's widget locations, for React (`widgets.sidebar`)
     *
     * @return array<string, mixed>
     */
    public function widgets(): array
    {
        $locations = array_values(array_filter(
            (array) config('cms.widget_locations', []),
            fn ($location) => Widgets::hasLocation((string) $location),
        ));

        return $locations === [] ? [] : Widgets::props($locations);
    }

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    */

    /**
     * blade or inertia
     */
    public function views(): string
    {
        return config('cms.views', 'blade') === 'inertia' ? 'inertia' : 'blade';
    }

    /**
     * The Blade layout of the admin or the public pages
     */
    public function layout(string $area = 'public'): string
    {
        return (string) config("cms.layouts.{$area}", $area === 'admin' ? 'cms::layouts.admin' : 'cms::layouts.public');
    }

    public function layoutSection(): string
    {
        return (string) config('cms.layouts.section', 'content');
    }

    /**
     * A setting from the Site page, or null when settings aren't registered
     */
    protected function setting(string $key): mixed
    {
        if (! config('cms.settings', true)) {
            return null;
        }

        try {
            return Settings::get($key);
        } catch (Throwable) {
            return null;
        }
    }
}
