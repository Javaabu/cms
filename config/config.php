<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Some config option
    |--------------------------------------------------------------------------
    |
    | Give a description of what each config option is like this
    |
    */

    /**
     * The model classes that are used in this application. You can extend the
     * classes and override from here
     */
    'models'            => [
        'admin'            => \App\Models\User::class,
        'user'             => \App\Models\User::class,
        'post'             => \Javaabu\Cms\Models\Post::class,
        'post_type'        => \Javaabu\Cms\Models\PostType::class,
        'category'         => \Javaabu\Cms\Models\Category::class,
        'category_type'    => \Javaabu\Cms\Models\CategoryType::class,
    ],

    'web' => [
        'controllers'        => [
            'posts'            => \Javaabu\Cms\Http\Controllers\PostsController::class,
        ],
    ],

    'admin' => [
        'controllers'        => [
            'posts'            => \Javaabu\Cms\Http\Controllers\Admin\PostsController::class,
            'categories'       => \Javaabu\Cms\Http\Controllers\Admin\CategoriesController::class,
        ]
    ],

    /**
     * This config section defines the policies that are used in the CMS package.
     * Not all applications will be having the same policies, so you can define the
     * policies that you want to use in the application for CMS models here.
     */
    'policies'          => [
        'post'             => \Javaabu\Cms\Policies\PostPolicy::class,
        'post_type'        => \Javaabu\Cms\Policies\PostTypePolicy::class,
        'category'         => \Javaabu\Cms\Policies\CategoryPolicy::class,
        'category_type'    => \Javaabu\Cms\Policies\CategoryTypePolicy::class,
    ],

    'should_translate' => false,
];
