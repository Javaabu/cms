<?php

namespace Javaabu\Cms;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Translatable\Http\Controllers\Admin\CategoriesController;
use Javaabu\Cms\Translatable\Http\Controllers\Admin\PostsController as AdminPostsController;
use Javaabu\Cms\Translatable\Http\Controllers\PostsController;
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

            $category_type = CategoryType::whereSlug($slug . '-categories')->first();

            if (! $category_type) {
                $category_type = new CategoryType();
            }

            $category_type->name = $data['name'] ?? $name . ' Categories';
            $category_type->singular_name = $data['name_singular'] ?? Str::singular($data['name'] ?? $name) . ' Category';
            $category_type->slug = $slug . '_categories';

            $category_type->save();

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

    public function registerAdminRoutes()
    {
        if (config('cms.should_translate')) {
            $this->registerTranslatableAdminRoutes();
        } else {
            $this->registerNormalAdminRoutes();
        }
    }

    public function registerNormalRoutes(): void
    {
        $root_slugs = app(RootSlugsRegistrar::class)->getSlugs();
        $post_types = $root_slugs['post_type'] ?? [];
        /** @var  PostType $post_type */
        foreach ($post_types as $post_type) {
            Route::get($post_type->slug, [config('cms.web.controllers.posts'), 'index'])
                ->defaults('web_post_type_slug', $post_type->slug)
                ->name('index.' . $post_type->slug);

            Route::get($post_type->slug . '/{post_slug}', [config('cms.web.controllers.posts'), 'show'])
                ->defaults('web_post_type_slug', $post_type)
                ->name('show.' . $post_type->slug);

//            Route::get($post_type->slug . '/{post_slug}/files', [PostsController::class, 'downloadFiles'])
//                ->defaults('web_post_type_slug', $post_type)
//                ->name('web.posts.show.files.' . $post_type->slug);
        }
    }

    public function registerNormalAdminRoutes(): void
    {
        /**
         * Categories
         */
        Route::group([
            'prefix' => 'category-types/{category_type}',
            'as'     => 'categories.',
        ], function () {
            Route::match(['PUT', 'PATCH'], '/', [config('cms.admin.controllers.categories'), 'bulk'])->name('bulk');
            Route::get('/trash', [config('cms.admin.controllers.categories'), 'trash'])->name('trash');
            Route::post('/{id}/restore', [config('cms.admin.controllers.categories'), 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [config('cms.admin.controllers.categories'), 'forceDelete'])->name('force-delete');
            Route::get('/', [config('cms.admin.controllers.categories'), 'index'])->name('index');
            Route::get('/create', [config('cms.admin.controllers.categories'), 'create'])->name('create');
            Route::post('/', [config('cms.admin.controllers.categories'), 'store'])->name('store');

            Route::get('/{category}', [config('cms.admin.controllers.categories'), 'show'])->name('show');
            Route::get('/{category}/edit', [config('cms.admin.controllers.categories'), 'edit'])->name('edit');
            Route::match(['PUT', 'PATCH'], '/{category}', [config('cms.admin.controllers.categories'), 'update'])->name('update');
            Route::delete('/{category}', [config('cms.admin.controllers.categories'), 'destroy'])->name('destroy');
        });
        /**
         * Post Types
         */
        Route::group([
            'prefix' => '{post_type}',
            'as' => 'posts.',
        ], function () {
            Route::match(['PUT', 'PATCH'], '/', [config('cms.admin.controllers.posts'), 'bulk'])->name('bulk');
            Route::get('/trash', [config('cms.admin.controllers.posts'), 'trash'])->name('trash');
            Route::post('/{id}/restore', [config('cms.admin.controllers.posts'), 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [config('cms.admin.controllers.posts'), 'forceDelete'])->name('force-delete');
            Route::get('/', [config('cms.admin.controllers.posts'), 'index'])->name('index');
            Route::get('/create', [config('cms.admin.controllers.posts'), 'create'])->name('create');
            Route::post('/', [config('cms.admin.controllers.posts'), 'store'])->name('store');
            Route::get('/{post}', [config('cms.admin.controllers.posts'), 'show'])->name('show');
            Route::get('/{post}/edit', [config('cms.admin.controllers.posts'), 'edit'])->name('edit');
            Route::match(['PUT', 'PATCH'], '/{post}', [config('cms.admin.controllers.posts'), 'update'])->name('update');
            Route::delete('/{post}', [config('cms.admin.controllers.posts'), 'destroy'])->name('destroy');
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
            $this->registerNormalAdminRoutes();
        });
    }

    public function adminMenuItems(array $menus = [])
    {
        $all_post_types = PostType::all();

        foreach ($all_post_types as $post_type) {
            $name = Str::title($post_type->name);
            $children = [
                MenuItem::make($name)
                    ->controller(config('cms.admin.controllers.posts'))
                    ->can('view_' . $post_type->permission_slug)
                    ->active(optional(request()->route('post_type'))->slug == $post_type->slug)
//                    ->url(config('cms.should_translate')
//                        ? translate_route('admin.posts.index', $post_type->slug)
//                        : route('admin.posts.index', $post_type->slug)
//                    )
                    ->url(translate_route('admin.posts.index', $post_type->slug))
                    ->icon('zmdi-' . $post_type->icon)
                    ->count(Post::query()->userVisibleForPostType($post_type)->postType($post_type->slug)->pending()),
            ];

            if ($post_type->hasFeature(PostTypeFeatures::CATEGORIES)) {
                $children[] = MenuItem::make(_d(':name Categories', ['name' => Str::singular($name)]))
                    ->controller(config('cms.admin.controllers.categories'))
                    ->can('view_' . Str::singular($post_type->permission_slug) . '_categories')
                    ->active(optional(request()->route('category_type'))->slug == Str::singular($post_type->slug) . '-categories')
//                    ->url(config('cms.should_translate')
//                        ? translate_route('admin.categories.index', Str::singular($post_type->slug) . '-categories')
//                        : route('admin.categories.index', Str::singular($post_type->slug) . '-categories')
//                    )
                    ->url(translate_route('admin.categories.index', Str::singular($post_type->slug) . '-categories'))
                ;

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

    /**
     * Load up permissions for all the Post Types
     *
     * @return array
     */
    public function seedPostTypePermissions(array $existing_permissions = []): array
    {
        $data = [];
        $all_post_types = PostType::all();

        foreach ($all_post_types as $post_type) {
            $permissions = $this->constructPostTypePermissions($post_type);
            $existing = $existing_permissions[$post_type->permission_slug] ?? [];
            $data[$post_type->permission_slug] = array_merge($existing, $permissions);
        }

        return array_merge($existing_permissions, $data);
    }

    /**
     * Permission template for the post types
     *
     * @param PostType $post_type
     * @return string[]
     */
    protected function constructPostTypePermissions(PostType $post_type): array
    {
        $slug = $post_type->permission_slug;
        $title = Str::lower($post_type->name);

        return [
            'edit_' . $slug                => 'Edit own ' . $title,
            'edit_others_' . $slug         => 'Edit all ' . $title,
            'delete_' . $slug              => 'Delete own ' . $title,
            'delete_others_' . $slug       => 'Delete all ' . $title,
            'view_' . $slug                => 'View own ' . $title,
            'view_others_' . $slug         => 'View all ' . $title,
            'force_delete_' . $slug        => 'Force delete own ' . $title,
            'force_delete_others_' . $slug => 'Force delete all ' . $title,
            'publish_' . $slug             => 'Publish own ' . $title,
            'publish_others_' . $slug      => 'Publish all ' . $title,
            'import_' . $slug              => 'Import ' . $title,
        ];
    }

    /**
     * Load up permissions for all the Category Types
     *
     * @returns array;
     */
    public function seedCategoryTypePermissions(array $existing_permissions = []): array
    {
        $data = [];
        $all_category_types = CategoryType::all();

        foreach ($all_category_types as $category_type) {
            $permissions = $this->constructCategoryTypePermissions($category_type);
            $existing = $existing_permissions[$category_type->permission_slug] ?? [];
            $data[$category_type->permission_slug] = array_merge($existing, $permissions);
        }
        return array_merge($existing_permissions, $data);
    }

    /**
     * Permission template for the category types
     *
     * @param CategoryType $category_type
     * @return string[]
     */
    protected function constructCategoryTypePermissions(CategoryType $category_type): array
    {
        $slug = $category_type->permission_slug;
        $title = Str::lower($category_type->name);

        return [
            'edit_' . $slug   => 'Edit ' . $title,
            'delete_' . $slug => 'Delete ' . $title,
            'view_' . $slug   => 'View ' . $title,
            'import_' . $slug => 'Import ' . $title,
        ];
    }
}
