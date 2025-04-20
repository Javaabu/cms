<?php

namespace Javaabu\Cms;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Javaabu\Cms\Http\Controllers\PostsController;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;
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
        Route::group([
            'prefix' => '{post_type}',
            'as' => 'cms::',
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
}
