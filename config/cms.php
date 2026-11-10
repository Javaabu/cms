<?php

use Javaabu\Cms\Http\Controllers\DashboardController;
use Javaabu\Cms\Http\Controllers\HomeController;
use Javaabu\Cms\Policies\DashboardPolicy;
use Javaabu\Cms\Widgets\RecentPostsWidget;
use Javaabu\Cms\Widgets\TermsWidget;

return [

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | The stack every page renders with: blade (javaabu/forms components) or
    | inertia (React pages shipped through javaabu/manifest). With
    | sync_packages on, the CMS also sets this on post-types, widgets,
    | dynamic-menubuilder, settings, mediapicker and permissions, so the whole
    | admin uses one stack. Requests that expect JSON always get JSON.
    |
    */

    'views' => env('CMS_VIEWS', 'blade'),

    /*
    | The Blade layouts pages extend, and the section they fill. The CMS ships
    | an admin layout (navigation and content) and a public theme (header
    | menu, content, sidebar and footer widgets, footer menu). Point these at
    | your own layouts to use them instead.
    */

    'layouts' => [
        'admin' => 'cms::layouts.admin',
        'public' => 'cms::layouts.public',
        'section' => 'content',
    ],

    /*
    | Apply `views` and `layouts` to the packages the CMS is made of. Turn off
    | to configure each package yourself.
    */

    'sync_packages' => true,

    /*
    |--------------------------------------------------------------------------
    | Default content model
    |--------------------------------------------------------------------------
    |
    | What the CMS registers for you. Remove an entry to leave it out, then
    | register your own (Javaabu\Cms\Content\Defaults has the definitions, to
    | start from). Post types and taxonomies are javaabu/post-types', menu
    | locations javaabu/dynamic-menubuilder's (key => max depth) and widget
    | locations and widget types javaabu/widgets'.
    |
    */

    'taxonomies' => ['categories', 'tags'],

    'post_types' => ['page', 'news'],

    'menu_locations' => [
        'header' => 2,
        'footer' => 1,
    ],

    'widget_locations' => ['sidebar', 'footer'],

    'widgets' => [
        RecentPostsWidget::class,
        TermsWidget::class,
    ],

    /*
    | Register the "Site" settings page (javaabu/settings): site name,
    | tagline, front page and footer text.
    */

    'settings' => true,

    /*
    |--------------------------------------------------------------------------
    | Home page
    |--------------------------------------------------------------------------
    |
    | Without a front page chosen in the site settings, the home page lists
    | the latest posts of this post type.
    |
    */

    'home' => [
        'post_type' => 'news',
        'count' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | How many recently edited posts the dashboard lists.
    |
    */

    'dashboard' => [
        'recent' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Controllers and authorization
    |--------------------------------------------------------------------------
    |
    | Replace a controller with a subclass, or pass `controllers` to the route
    | macros. The dashboard policy can also be overridden with
    | config('permissions.policies') or Gate::policy(). Turn off
    | register_permissions to register your own.
    |
    */

    'controllers' => [
        'dashboard' => DashboardController::class,
        'home' => HomeController::class,
    ],

    'policies' => [
        'dashboard' => DashboardPolicy::class,
    ],

    'register_permissions' => true,

    /*
    |--------------------------------------------------------------------------
    | Translations
    |--------------------------------------------------------------------------
    |
    | With javaabu/translatable installed, pages, news, terms, menus, widgets
    | and the site settings are translatable, and the public theme shows a
    | language switcher. Turn off to keep one language anyway.
    |
    */

    'translatable' => true,

    /*
    |--------------------------------------------------------------------------
    | Upgrading from v1
    |--------------------------------------------------------------------------
    |
    | v1 kept its own `posts` table, which javaabu/post-types needs. When a v1
    | `posts` table is found, a migration renames v1's tables to
    | `cms_v1_{table}` before post-types creates its own, and
    | `php artisan cms:import-v1` copies the content over.
    |
    */

    'upgrade' => [
        'rename_v1_tables' => true,
        'prefix' => 'cms_v1_',
    ],

];
