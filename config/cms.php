<?php

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
    |
    */

    'default_post_types' => [
        'news' => [
            'name' => 'News',
            'name_dv' => 'ޚަބަރު',
            'singular_name' => 'News Article',
            'icon' => 'zmdi-assignment',
            'category_type' => 'news-categories',
            'features' => [
                'image-gallery' => true,
                'categories' => true,
                'featured-image' => true,
                'excerpt' => true,
            ],
            'description' => 'Latest news and updates',
            'og_description' => 'Stay updated with our latest news',
        ],
        'blog' => [
            'name' => 'Blog Posts',
            'name_dv' => 'ބްލޮގް',
            'singular_name' => 'Blog Post',
            'icon' => 'zmdi-library',
            'category_type' => 'blog-categories',
            'features' => [
                'image-gallery' => true,
                'categories' => true,
                'featured-image' => true,
                'excerpt' => true,
            ],
            'description' => 'Blog articles and insights',
            'og_description' => 'Read our latest blog posts',
        ],
        'downloads' => [
            'name' => 'Downloads',
            'name_dv' => 'ޑައުންލޯޑްސް',
            'singular_name' => 'Download',
            'icon' => 'zmdi-download',
            'category_type' => 'download-categories',
            'features' => [
                'documents' => true,
                'categories' => true,
            ],
            'description' => 'Downloadable files and documents',
        ],
        'announcements' => [
            'name' => 'Announcements',
            'name_dv' => 'އެނައުންސްމެންޓުތައް',
            'singular_name' => 'Announcement',
            'icon' => 'zmdi-alert-triangle',
            'category_type' => 'announcement-categories',
            'features' => [
                'documents' => true,
                'document-number' => true,
                'expireable' => true,
                'categories' => true,
                'reference-no' => true,
            ],
            'description' => 'Important announcements and notices',
        ],
        'publications' => [
            'name' => 'Publications',
            'name_dv' => 'ޝާޢިއުކުރުންތައް',
            'singular_name' => 'Publication',
            'icon' => 'zmdi-book',
            'category_type' => 'publication-categories',
            'features' => [
                'documents' => true,
                'categories' => true,
                'featured-image' => true,
            ],
            'description' => 'Published documents and materials',
        ],
        'jobs' => [
            'name' => 'Jobs',
            'name_dv' => 'ވަޒީފާތައް',
            'singular_name' => 'Job',
            'icon' => 'zmdi-case',
            'category_type' => 'job-categories',
            'features' => [
                'documents' => true,
                'document-number' => true,
                'expireable' => true,
                'categories' => true,
                'reference-no' => true,
            ],
            'description' => 'Job openings and career opportunities',
        ],
        'galleries' => [
            'name' => 'Galleries',
            'name_dv' => 'ގެލަރީތައް',
            'singular_name' => 'Gallery',
            'icon' => 'zmdi-collection-image-o',
            'category_type' => 'gallery-categories',
            'features' => [
                'image-gallery' => true,
                'format' => true,
                'categories' => true,
            ],
            'description' => 'Photo and video galleries',
        ],
        'tenders' => [
            'name' => 'Tenders',
            'name_dv' => 'ބީލަންތައް',
            'singular_name' => 'Tender',
            'icon' => 'zmdi-assignment',
            'category_type' => 'tender-categories',
            'features' => [
                'documents' => true,
                'document-number' => true,
                'expireable' => true,
                'categories' => true,
                'reference-no' => true,
                'gazette-link' => true,
            ],
            'description' => 'Tender notices and bids',
        ],
        'reports' => [
            'name' => 'Reports',
            'name_dv' => 'ރިޕޯތްތައް',
            'singular_name' => 'Report',
            'icon' => 'zmdi-collection-text',
            'category_type' => 'report-categories',
            'features' => [
                'documents' => true,
                'categories' => true,
                'document-number' => true,
            ],
            'description' => 'Reports and statistics',
        ],
        'pages' => [
            'name' => 'Pages',
            'name_dv' => 'ސްފްޙާތައް',
            'singular_name' => 'Page',
            'icon' => 'zmdi-file',
            'category_type' => null,
            'features' => [
                'page-style' => true,
            ],
            'description' => 'Static pages and content',
        ],
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

];
