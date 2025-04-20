<?php

namespace Javaabu\Cms;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Http\Controllers\PostsController;
use Javaabu\Cms\Http\Controllers\Admin\PostsController as AdminPostsController;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\MenuBuilder\Menu\MenuItem;
use Javaabu\Translatable\Facades\Languages;

class Cms {
    public function registerPostTypes($postTypes): void
    {
        $count = 0;
        foreach ($postTypes as $slug => $data) {
            $type = PostType::whereSlug($slug)->first();

            if (! $type) {
                $type = new PostType();
            }

            $name = Str::title(str_replace('-', ' ', $slug));
            $type->name = $data['name'] ?? $name;
            $type->singular_name = $data['name_singular'] ?? Str::singular($data['name'] ?? $name);
            if (config('cms.should_translate')) $type->lang = Languages::default();

            $type->slug = $slug;
            $type->icon = $data['icon'];


            $category_type = CategoryType::whereSlug(Str::singular($slug) . '-categories')->first();
            $type->categoryType()->associate($category_type);

            $type->features = $data['features'];

            $type->order_column = $count;

            $type->save();

            $count++;
        }
    }

    public function registerRoutes()
    {
        if (config('cms.should_translate')) {
            $this->registerTranslatableRoutes();
        } else {
            $this->registerNormalRoutes();
        }
    }

    public function registerNormalRoutes(): void
    {
        $root_slugs = app(RootSlugsRegistrar::class)->getSlugs();
        $post_types = $root_slugs['post_type'] ?? [];
        /** @var  PostType $post_type */
        foreach ($post_types as $post_type) {
            Route::get($post_type->slug, [PostsController::class, 'index'])
                ->defaults('web_post_type_slug', $post_type->slug)
                ->name('cms::posts.index.' . $post_type->slug);

            Route::get($post_type->slug . '/{post_slug}', [PostsController::class, 'show'])
                ->defaults('web_post_type_slug', $post_type)
                ->name('cms::posts.show.' . $post_type->slug);

//            Route::get($post_type->slug . '/{post_slug}/files', [PostsController::class, 'downloadFiles'])
//                ->defaults('web_post_type_slug', $post_type)
//                ->name('cms::posts.show.files.' . $post_type->slug);
        }
    }

    public function registerAdminRoutes(): void
    {
        Route::group([
            'prefix' => '{post_type}',
            'as' => 'posts.',
        ], function () {
            Route::match(['PUT', 'PATCH'], '/', [PostsController::class, 'bulk'])->name('bulk');
            Route::get('/trash', [PostsController::class, 'trash'])->name('trash');
            Route::post('/{id}/restore', [PostsController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [PostsController::class, 'forceDelete'])->name('force-delete');
            Route::get('/', [PostsController::class, 'index'])->name('index');
            Route::get('/create', [PostsController::class, 'create'])->name('create');
            Route::post('/', [PostsController::class, 'store'])->name('store');
            Route::get('/{post}', [PostsController::class, 'show'])->name('show');
            Route::get('/{post}/edit', [PostsController::class, 'edit'])->name('edit');
            Route::match(['PUT', 'PATCH'], '/{post}', [PostsController::class, 'update'])->name('update');
            Route::delete('/{post}', [PostsController::class, 'destroy'])->name('destroy');
        });
    }

    /**
     * @return void
     */
    public function registerTranslatableRoutes(): void
    {
        Route::group([
            'prefix' => '{language}',
        ], function () {
            $this->registerNormalRoutes();
        });
    }

    public function registerTranslatableAdminRoutes()
    {
        Route::group([
            'prefix' => '{language}',
        ], function () {
            $this->registerAdminRoutes();
        });
    }

    public function addToSidebar($menus)
    {
        $all_post_types = PostType::all();

        foreach ($all_post_types as $post_type) {
            $name = Str::title($post_type->name_en);
            $children = [
                MenuItem::make($name)
                    ->can('view_' . $post_type->permission_slug)
                    ->active(optional(request()->route('post_type'))->slug == $post_type->slug)
                    ->url(config('cms.should_translate')
                        ? translate_route('admin.posts.index', $post_type->slug)
                        : route('admin.posts.index', $post_type->slug)
                    )
                    ->icon('zmdi-' . $post_type->icon)
                    ->count(Post::query()->userVisibleForPostType($post_type)->postType($post_type->slug)->pending()),
            ];

            if ($post_type->hasFeature(PostTypeFeatures::CATEGORIES)) {
                $children[] = MenuItem::make(_d(':name Categories', ['name' => Str::singular($name)]))
                    ->can('view_' . Str::singular($post_type->permission_slug) . '_categories')
                    ->url(config('cms.should_translate')
                        ? translate_route('admin.categories.index', Str::singular($post_type->slug) . '-categories')
                        : route('admin.categories.index', Str::singular($post_type->slug) . '-categories')
                    )
                    ->active(optional(request()->route('category_type'))->slug == Str::singular($post_type->slug) . '-categories');

                $menus[] =
                    MenuItem::make($name)
                        ->icon('zmdi-' . $post_type->icon)
                        ->can('view_' . $post_type->permission_slug)
                        ->children($children);
            } else {
                $menus = array_merge($menus, $children);
            }
        }

        return $menus;
    }
}
