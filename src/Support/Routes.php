<?php /** @noinspection ALL */

namespace Javaabu\Cms\Support;


use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Http\Controllers\Admin\CategoriesController;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Http\Controllers\Admin\PostsController;
use Javaabu\MenuBuilder\Menu\Menu;
use Javaabu\MenuBuilder\Menu\MenuItem;
use Illuminate\Support\Str;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Http\Controllers\Admin\MediaController;

class Routes
{
    /**
     * Register admin routes for CMS
     *
     * @param string|null $prefix
     * @param string|null $domain
     * @param array $middleware
     * @return void
     */
    public static function admin(
        ?string $prefix = 'admin',
        ?string $domain = null,
        array $middleware = ['forms:material-admin-26']
    ): void {

        Route::bind('post_type', function ($value, $route) {
            $model = config('cms.models.post_type', PostType::class);
            try {
                return $model::whereSlug($value)
                    ->firstOrFail();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                abort(404);
            }

            return $value;
        });

        Route::bind('post', function ($value, $route) {
            $post_type = $route->parameter('post_type') ?: $route->parameter('postType');
            $post_type_slug = is_object($post_type) ? $post_type->slug : $post_type;
            $model = config('cms.models.post', Post::class);

            try {
                $query = $model::where('type', $post_type_slug ?: -1);

                if (Str::startsWith($route->getName(), 'admin.')) {
                    return $query->findOrFail($value);
                }

                $language = $route->parameter('language');
                if ($language && method_exists($model, 'scopeNotHiddenOfLocale')) {
                    $query->notHiddenOfLocale($language);
                }

                return $query->whereSlug($value)
                    ->publishedOrPreview()
                    ->firstOrFail();

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                abort(404);
            }

            return $value;
        });

        Route::bind('category_type', function ($value, $route) {
            $model = config('cms.models.category_type', CategoryType::class);
            try {
                return $model::where('slug', $value)
                    ->firstOrFail();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                abort(404);
            }
        });

        Route::bind('category', function ($value, $route) {
            $category_type = $route->parameter('category_type');
            $category_type_id = is_object($category_type) ? $category_type->id : $category_type;
            $model = config('cms.models.category', Category::class);

            try {
                return $model::where('type_id', $category_type_id ?: -1)
                    ->findOrFail($value);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                abort(404);
            }
        });


        // Categories Routes
        Route::group([
            'prefix' => 'category-types',
            'as' => 'categories.',
        ], function () {
            $controller = config('cms.admin.controllers.categories', \Javaabu\Cms\Http\Controllers\Admin\CategoriesController::class);

            Route::get('{category_type}', [$controller, 'index'])->name('index');
            Route::get('{category_type}/create', [$controller, 'create'])->name('create');
            Route::post('{category_type}', [$controller, 'store'])->name('store');
            Route::get('{category_type}/{category}/edit', [$controller, 'edit'])->name('edit');
            Route::put('{category_type}/{category}', [$controller, 'update'])->name('update');
            Route::delete('{category_type}/{category}', [$controller, 'destroy'])->name('destroy');
            Route::match(['PUT', 'PATCH'], '/', [$controller, 'bulk'])->name('bulk');
        });

        /* // Posts Routes
         Route::get('{post_type}', '\Javaabu\Cms\Http\Controllers\Admin\PostsController@index')
             ->name('posts.index');
         Route::get('{post_type}/trash', '\Javaabu\Cms\Http\Controllers\Admin\PostsController@index')
             ->name('posts.index');
         Route::get('{post_type}/create', '\Javaabu\Cms\Http\Controllers\Admin\PostsController@create')
             ->name('posts.create');
         Route::post('{post_type}', '\Javaabu\Cms\Http\Controllers\Admin\PostsController@store')
             ->name('posts.store');
         Route::get('{post_type}/{post}', '\Javaabu\Cms\Http\Controllers\Admin\PostsController@show')
             ->name('posts.show');
         Route::get('{post_type}/{post}/edit', '\Javaabu\Cms\Http\Controllers\Admin\PostsController@edit')
             ->name('posts.edit');
         Route::put('{post_type}/{post}', '\Javaabu\Cms\Http\Controllers\Admin\PostsController@update')
             ->name('posts.update');
         Route::delete('{post_type}/{post}', '\Javaabu\Cms\Http\Controllers\Admin\PostsController@destroy')
             ->name('posts.destroy');*/

        Route::group([
            'prefix' => '{post_type}',
            'as' => 'posts.',
        ], function () {
            $controller = config('cms.admin.controllers.posts', \Javaabu\Cms\Http\Controllers\Admin\PostsController::class);

            Route::match(['PUT', 'PATCH'], '/', [$controller, 'bulk'])->name('bulk');
            Route::get('/trash', [$controller, 'trash'])->name('trash');
            Route::post('/{post:id}/restore', [$controller, 'restore'])->name('restore');
            Route::delete('/{post:id}/force-delete', [$controller, 'forceDelete'])->name('force-delete');
            Route::get('/', [$controller, 'index'])->name('index');
            Route::get('/create', [$controller, 'create'])->name('create');
            Route::post('/', [$controller, 'store'])->name('store');
            Route::get('/{post:id}', [$controller, 'show'])->name('show');
            Route::get('/{post:id}/edit', [$controller, 'edit'])->name('edit');
            Route::match(['PUT', 'PATCH'], '/{post:id}', [$controller, 'update'])->name('update');
            Route::delete('/{post:id}', [$controller, 'destroy'])->name('destroy');
        });
    }

    /**
     * Register public/web routes for CMS
     *
     * @param string|null $prefix
     * @param string|null $domain
     * @param array $middleware
     * @return void
     */
    public static function web(
        ?string $prefix = null,
        ?string $domain = null,
        array $middleware = ['web']
    ): void {

        Route::bind('post_slug', function ($value, $route) {
            $language = $route->parameter('language');
            $post_type = $route->parameter('post_type') ?: $route->parameter('postType');
            $post_type_slug = is_object($post_type) ? $post_type->slug : $post_type;
            $model = config('cms.models.post', Post::class);

            try {
                $query = $model::where('type', $post_type_slug ?: -1);

                if ($language && method_exists($model, 'scopeNotHiddenOfLocale')) {
                    $query->notHiddenOfLocale($language);
                }

                return $query->publishedOrPreview()
                    ->whereSlug($value)
                    ->firstOrFail();

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                abort(404);
            }

            return $value;
        });

        Route::bind('page_slug', function ($value, $route) {
            $language = $route->parameter('language');
            $model = config('cms.models.post', Post::class);

            try {
                $query = $model::where('type', 'pages')
                    ->published();

                if ($language && method_exists($model, 'scopeNotHiddenOfLocale')) {
                    $query->notHiddenOfLocale($language);
                }

                return $query->whereSlug($value)
                    ->firstOrFail();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                abort(404);
            }

            return $value;
        });

        $registrar = Route::middleware($middleware);

        if ($domain) {
            $registrar->domain($domain);
        }

        if ($prefix) {
            $registrar->prefix($prefix);
        }

        $registrar->group(function () {
            $controller = config('cms.web.controllers.posts', \Javaabu\Cms\Http\Controllers\PostsController::class);

            // Post Type Index Routes
            Route::get('{postType}', [$controller, 'index'])
                ->name('posts.index');

            // Post Single View Route
            Route::get('{postType}/{post}', [$controller, 'show'])
                ->name('posts.show');

            // Category Posts Route
            Route::get('{postType}/category/{category}', [$controller, 'category'])
                ->name('posts.category');
        });
    }

    /**
     * Register custom post type routes
     *
     * @param string $postTypeSlug
     * @param string|null $prefix
     * @param string|null $domain
     * @param array $middleware
     * @param string|null $controller
     * @return void
     */
    public static function customPostType(
        string $postTypeSlug,
        ?string $prefix = null,
        ?string $domain = null,
        array $middleware = ['web'],
        ?string $controller = null
    ): void {
        $controller = $controller ?? config('cms.web.controllers.posts', \Javaabu\Cms\Http\Controllers\PostsController::class);

        $registrar = Route::middleware($middleware);

        if ($domain) {
            $registrar->domain($domain);
        }

        if ($prefix) {
            $registrar->prefix($prefix);
        }

        $registrar->group(function () use ($postTypeSlug, $controller) {
            // Post Type Index
            Route::get($postTypeSlug, $controller . '@index')
                ->name("post-types.{$postTypeSlug}.index")
                ->defaults('postType', $postTypeSlug);

            // Single Post View
            Route::get("{$postTypeSlug}/{post}", $controller . '@show')
                ->name("post-types.{$postTypeSlug}.show")
                ->defaults('postType', $postTypeSlug);

            // Category Filter
            Route::get("{$postTypeSlug}/category/{category}", $controller . '@category')
                ->name("post-types.{$postTypeSlug}.category")
                ->defaults('postType', $postTypeSlug);
        });
    }


    public static function adminSideBarMenuItems(): array
    {
        $menus = [];
        $post_type_model = config('cms.models.post_type', PostType::class);
        $post_model = config('cms.models.post', Post::class);
        $all_post_types = $post_type_model::all();

        foreach ($all_post_types as $post_type) {
            $name = Str::title($post_type->name);
            $children = [
                MenuItem::make($name)
                    ->can('view_' . $post_type->permission_slug)
                    ->active(optional(request()->route('post_type'))->slug == $post_type->slug)
                    ->url(config('cms.should_translate')
                        ? translate_route('admin.posts.index', $post_type->slug)
                        : route('admin.posts.index', $post_type->slug))
                    ->icon($post_type->icon)
                    ->count($post_model::query()->userVisibleForPostType($post_type)->postType($post_type->slug)->pending()),
            ];

            if ($post_type->hasFeature(PostTypeFeatures::CATEGORIES)) {
                $children[] = MenuItem::make(_d(':name Categories', ['name' => Str::singular($name)]))
                    ->can('view_' . Str::singular($post_type->permission_slug) . '_categories')
                    ->url(config('cms.should_translate')
                        ? translate_route('admin.categories.index', Str::singular($post_type->slug) . '-categories')
                        : route('admin.categories.index', Str::singular($post_type->slug) . '-categories'))
                    ->active(optional(request()->route('category_type'))->slug == Str::singular($post_type->slug) . '-categories');

                $menus[] =
                    MenuItem::make($name)
                        ->icon($post_type->icon)
                        ->can('view_' . $post_type->permission_slug)
                        ->children($children);
            } else {
                $menus = array_merge($menus, $children);
            }
        }

        return $menus;
    }
}





