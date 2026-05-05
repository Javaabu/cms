<?php

use Javaabu\Cms\PostTypes\PostType;

return [

    /*
    |--------------------------------------------------------------------------
    | Post Types
    |--------------------------------------------------------------------------
    |
    | Define your custom post types here. Each post type can have its own
    | configuration for features, category types, and routing.
    |
    */

    'post_types' => [
        // Example:
        // 'news' => [
        //     'label' => 'News',
        //     'singular_label' => 'News Article',
        //     'features' => ['categories', 'featured_image', 'excerpt'],
        //     'category_types' => ['news-categories'],
        //     'admin_route_prefix' => 'admin.news',
        //     'public_route_prefix' => 'news',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Types
    |--------------------------------------------------------------------------
    |
    | Define your custom category types here. Category types are used to
    | organize posts into different taxonomies.
    |
    */

    'category_types' => [
        // Example:
        // 'news-categories' => [
        //     'label' => 'News Categories',
        //     'singular_label' => 'News Category',
        //     'hierarchical' => true,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Post Type Features
    |--------------------------------------------------------------------------
    |
    | Define the default features that all post types should have.
    | Individual post types can override these settings.
    |
    */

    'default_features' => [
        'title',
        'content',
        'excerpt',
        'featured_image',
        'categories',
        'publish_date',
        'status',
        'slug',
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Settings
    |--------------------------------------------------------------------------
    |
    | Configure media library settings for posts.
    |
    */

    'media' => [
        'featured_image_sizes' => [
            'thumb' => [300, 300],
            'medium' => [600, 400],
            'large' => [1200, 800],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Configure pagination for post listings.
    |
    */

    'pagination' => [
        'per_page' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Settings
    |--------------------------------------------------------------------------
    |
    | Enable or disable multi-language translations for posts, categories, etc.
    |
    */

    'should_translate' => false,

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The default language to use when translations are enabled.
    |
    */

    'default_language' => 'en',

    /*
    |--------------------------------------------------------------------------
    | CMS Models
    |--------------------------------------------------------------------------
    |
    | Define the model classes used by the CMS. You can extend these classes
    | and override them here.
    |
    */

    'models' => [
        'post' => \Javaabu\Cms\Models\Post::class,
        'post_type' => \Javaabu\Cms\Models\PostType::class,
        'category' => \Javaabu\Cms\Models\Category::class,
        'category_type' => \Javaabu\Cms\Models\CategoryType::class,
        'tag' => \Javaabu\Cms\Models\Tag::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Controller Configuration
    |--------------------------------------------------------------------------
    |
    | Define controllers used by the CMS for web and admin routes.
    |
    */

    'web' => [
        'controllers' => [
            'posts' => \Javaabu\Cms\Http\Controllers\PostsController::class,
        ],
    ],

    'admin' => [
        'controllers' => [
            'posts' => \Javaabu\Cms\Http\Controllers\Admin\PostsController::class,
            'categories' => \Javaabu\Cms\Http\Controllers\Admin\CategoriesController::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Category Types
    |--------------------------------------------------------------------------
    |
    | Define default category types that can be installed during setup.
    | These will be created when running the setup command with --with-defaults.
    |
    */

    'default_category_types' => [
        'news-categories' => [
            'name' => 'News Categories',
            'singular_name' => 'News Category',
        ],
        'blog-categories' => [
            'name' => 'Blog Categories',
            'singular_name' => 'Blog Category',
        ],
        'download-categories' => [
            'name' => 'Download Categories',
            'singular_name' => 'Download Category',
        ],
        'announcement-categories' => [
            'name' => 'Announcement Categories',
            'singular_name' => 'Announcement Category',
        ],
        'publication-categories' => [
            'name' => 'Publication Categories',
            'singular_name' => 'Publication Category',
        ],
        'job-categories' => [
            'name' => 'Job Categories',
            'singular_name' => 'Job Category',
        ],
        'gallery-categories' => [
            'name' => 'Gallery Categories',
            'singular_name' => 'Gallery Category',
        ],
        'tender-categories' => [
            'name' => 'Tender Categories',
            'singular_name' => 'Tender Category',
        ],
        'report-categories' => [
            'name' => 'Report Categories',
            'singular_name' => 'Report Category',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Post Types
    |--------------------------------------------------------------------------
    |
    | Define default post types that can be installed during setup.
    | These will be created when running the setup command with --with-defaults.
    | Supports both plain arrays and fluent definitions via:
    | Javaabu\Cms\PostTypes\PostType::make('news')->name(...)
    |
    */

    'default_post_types' => [
        PostType::make('news')
            ->name('News')
            ->nameDv('ޚަބަރު')
            ->singularName('News Article')
            ->icon('zmdi-assignment')
            ->categoryType('news-categories')
            ->features(['image-gallery', 'categories', 'featured-image', 'excerpt'])
            ->description('Latest news and updates')
            ->ogDescription('Stay updated with our latest news'),

        PostType::make('blog')
            ->name('Blog Posts')
            ->nameDv('ބްލޮގް')
            ->singularName('Blog Post')
            ->icon('zmdi-library')
            ->categoryType('blog-categories')
            ->features(['image-gallery', 'categories', 'featured-image', 'excerpt'])
            ->description('Blog articles and insights')
            ->ogDescription('Read our latest blog posts'),

        PostType::make('downloads')
            ->name('Downloads')
            ->nameDv('ޑައުންލޯޑްސް')
            ->singularName('Download')
            ->icon('zmdi-download')
            ->categoryType('download-categories')
            ->features(['documents', 'categories'])
            ->description('Downloadable files and documents'),

        PostType::make('announcements')
            ->name('Announcements')
            ->nameDv('އެނައުންސްމެންޓުތައް')
            ->singularName('Announcement')
            ->icon('zmdi-alert-triangle')
            ->categoryType('announcement-categories')
            ->features(['documents', 'document-number', 'expireable', 'categories', 'reference-no'])
            ->description('Important announcements and notices'),

        PostType::make('publications')
            ->name('Publications')
            ->nameDv('ޝާޢިއުކުރުންތައް')
            ->singularName('Publication')
            ->icon('zmdi-book')
            ->categoryType('publication-categories')
            ->features(['documents', 'categories', 'featured-image'])
            ->description('Published documents and materials'),

        PostType::make('jobs')
            ->name('Jobs')
            ->nameDv('ވަޒީފާތައް')
            ->singularName('Job')
            ->icon('zmdi-case')
            ->categoryType('job-categories')
            ->features(['documents', 'document-number', 'expireable', 'categories', 'reference-no'])
            ->description('Job openings and career opportunities'),

        PostType::make('galleries')
            ->name('Galleries')
            ->nameDv('ގެލަރީތައް')
            ->singularName('Gallery')
            ->icon('zmdi-collection-image-o')
            ->categoryType('gallery-categories')
            ->features(['image-gallery', 'format', 'categories'])
            ->description('Photo and video galleries'),

        PostType::make('tenders')
            ->name('Tenders')
            ->nameDv('ބީލަންތައް')
            ->singularName('Tender')
            ->icon('zmdi-assignment')
            ->categoryType('tender-categories')
            ->features(['documents', 'document-number', 'expireable', 'categories', 'reference-no', 'gazette-link'])
            ->description('Tender notices and bids'),

        PostType::make('reports')
            ->name('Reports')
            ->nameDv('ރިޕޯތްތައް')
            ->singularName('Report')
            ->icon('zmdi-collection-text')
            ->categoryType('report-categories')
            ->features(['documents', 'categories', 'document-number'])
            ->description('Reports and statistics'),

        PostType::make('pages')
            ->name('Pages')
            ->nameDv('ސްފްޙާތައް')
            ->singularName('Page')
            ->icon('zmdi-file')
            ->features(['page-style'])
            ->description('Static pages and content'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Categories
    |--------------------------------------------------------------------------
    |
    | Define default categories that can be installed during setup.
    | These will be created when running the setup command with --with-defaults.
    |
    */

    'default_categories' => [
        'news-categories' => [
            ['name' => 'General', 'name_dv' => 'އާއްމު ޚަބަރު', 'slug' => 'general'],

            ['name' => 'Press Releases', 'name_dv' => 'ނޫސް ބަޔާން', 'slug' => 'press-releases'],
        ],
        'blog-categories' => [
            ['name' => 'Technology',  'name_dv' => 'ޓެކްނޮލޮޖީ', 'slug' => 'technology'],
            ['name' => 'Business', 'name_dv' => 'ވިޔފާރި', 'slug' => 'business'],
        ],
        'job-categories' => [
            ['name' => 'Full Time', 'name_dv' => 'ފުލްޓައިމް', 'slug' => 'full-time'],
            ['name' => 'Part Time', 'name_dv' => 'ޕާޓްޓައިމް', 'slug' => 'part-time'],
            ['name' => 'Intern', 'name_dv' => 'އިންޓާން', 'slug' => 'intern'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    |
    | Here you can specify the table names to use for the CMS models.
    |
    */

    'database' => [
        'posts' => 'posts',
        'post_types' => 'post_types',
        'categories' => 'categories',
        'category_types' => 'category_types',
        'category_model' => 'category_model',
        'tags' => 'tags',
        'tag_model' => 'tag_model',
    ],

    /*
    |--------------------------------------------------------------------------
    | Root Slugs Settings
    |--------------------------------------------------------------------------
    |
    | Configure the cache for post type root slugs.
    |
    */

    'rootslugs' => [
        'cache' => [
            'key' => 'javaabu.cms.rootslugs',
            'expiration_time' => 86400, // 24 hours
            'store' => 'default',
        ],
    ],

];
