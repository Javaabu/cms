# Javaabu CMS

A flexible and extensible Content Management System package for Laravel applications. Built with support for custom post types, hierarchical categories, and rich content editing with Editor.js.

## Features

- 🎯 **Custom Post Types**: Define unlimited custom post types with configurable features
- 📁 **Hierarchical Categories**: Nested category support using Nestedset
- ✍️ **Rich Content Editor**: Integrated Editor.js support for modern content editing
- 🔐 **Permission System**: Built-in permission management for CMS operations
- 🌐 **Multi-language Ready**: Translation support for content
- 📱 **Responsive Admin**: Modern admin interface
- 🔌 **Extensible**: Easy to extend with custom controllers, views, and policies
- 🚀 **Easy Setup**: Artisan command for quick installation

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0
- MySQL/PostgreSQL database

## Installation

Install the package via Composer:

```bash
composer require javaabu/cms
```

Run the setup command:

```bash
php artisan cms:setup
```

This will:
- Publish the configuration file
- Publish and run migrations
- Optionally install default post types and categories
- Seed CMS permissions

### Quick Start with Defaults

To get started quickly with pre-configured post types and categories:

```bash
php artisan cms:setup --with-defaults
```

This installs 10 ready-to-use post types (News, Blog, Downloads, Announcements, Publications, Jobs, Galleries, Tenders, Reports, Pages) with their category types and sample categories.

You can customize these defaults in `config/cms.php` before running setup.

## Configuration

After installation, configure your post types in `config/cms.php`:

```php
'post_types' => [
    'news' => [
        'label' => 'News',
        'singular_label' => 'News Article',
        'features' => ['categories', 'featured_image', 'excerpt'],
        'category_types' => ['news-categories'],
    ],
    'blog' => [
        'label' => 'Blog Posts',
        'singular_label' => 'Blog Post',
        'features' => ['categories', 'featured_image', 'excerpt', 'video-link'],
        'category_types' => ['blog-categories'],
    ],
],

'category_types' => [
    'news-categories' => [
        'label' => 'News Categories',
        'singular_label' => 'News Category',
        'hierarchical' => true,
    ],
],
```

## Register Routes

Add CMS routes to your `routes/web.php`:

```php
use Javaabu\Cms\Support\Routes;

// Admin routes
Routes::admin(
    prefix: 'admin',
    middleware: ['web', 'auth', 'verified']
);

// Public routes
Routes::web();

// Or register custom post type routes
Routes::customPostType(
    postTypeSlug: 'news',
    prefix: 'news',
    middleware: ['web']
);
```

## Usage

### Creating Post Types

Post types can be created via:

1. **Database Seeder**:
```php
use Javaabu\Cms\Models\PostType;

PostType::create([
    'name' => 'News',
    'singular_name' => 'News Article',
    'slug' => 'news',
    'icon' => 'newspaper',
    'features' => [
        'categories' => true,
        'featured_image' => true,
        'excerpt' => true,
    ],
]);
```

2. **Admin Panel**: Navigate to `/admin/post-types` after setup

### Creating Posts

```php
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Enums\PostStatus;

$post = Post::create([
    'type' => 'news',
    'title' => 'Breaking News',
    'slug' => 'breaking-news',
    'content' => '<p>Content here...</p>',
    'excerpt' => 'Short description',
    'status' => PostStatus::PUBLISHED->value,
    'published_at' => now(),
]);

// Attach categories
$post->categories()->attach($categoryIds);
```

### Querying Posts

```php
use Javaabu\Cms\Models\Post;

// Get published posts of a type
$posts = Post::postType('news')
    ->published()
    ->ordered()
    ->paginate(15);

// Search posts
$posts = Post::postType('news')
    ->search('keyword')
    ->published()
    ->get();

// Get posts by year
$posts = Post::postType('news')
    ->publishedByYear(2024)
    ->get();
```

### Working with Categories

```php
use Javaabu\Cms\Models\Category;

// Get categories for select dropdown
$categories = Category::categoryList($typeId);

// Get nested categories
$categories = Category::categoryType($typeId)
    ->defaultOrder()
    ->get()
    ->toTree();
```

## Available Post Type Features

- `categories` - Category support
- `featured_image` - Featured image
- `excerpt` - Post excerpt
- `documents` - Document attachments
- `image_gallery` - Image gallery
- `video_link` - Video embed URL
- `document_number` - Document reference number
- `expireable` - Expiry date
- `format` - Post format (standard, video, gallery, etc.)
- `page_style` - Custom page styling
- `ref_no` - Reference number
- `gazette_link` - Gazette document link

## Permissions

The package dynamically creates permissions for each Post Type and Category Type you create. Permissions are based on the slug of the type.

### Post Type Permissions

For each Post Type (e.g., 'news'), the following permissions are created:
- `edit_{slug}` - Edit own posts (e.g., `edit_news`)
- `edit_others_{slug}` - Edit all posts (e.g., `edit_others_news`)
- `delete_{slug}` - Delete own posts
- `delete_others_{slug}` - Delete all posts
- `view_{slug}` - View own posts
- `view_others_{slug}` - View all posts
- `force_delete_{slug}` - Force delete own posts
- `force_delete_others_{slug}` - Force delete all posts
- `publish_{slug}` - Publish own posts
- `publish_others_{slug}` - Publish all posts
- `import_{slug}` - Import posts

### Category Type Permissions

For each Category Type (e.g., 'news-categories'), the following permissions are created:
- `edit_{slug}` - Edit categories (e.g., `edit_news_categories`)
- `delete_{slug}` - Delete categories
- `view_{slug}` - View categories
- `import_{slug}` - Import categories

### Seeding Permissions

Seed them using:

```php
use Javaabu\Cms\seeders\CmsPermissionsSeeder;

CmsPermissionsSeeder::seedPermissions();
```

Call this after creating your Post Types and Category Types to generate the appropriate permissions.

## Frontend Integration

The package provides models and data - implement your own views:

```blade
{{-- resources/views/posts/index.blade.php --}}
@foreach($posts as $post)
    <article>
        <h2>{{ $post->title }}</h2>
        <p>{{ $post->excerpt }}</p>
        <a href="{{ route('posts.show', [$post->type, $post->slug]) }}">
            Read More
        </a>
    </article>
@endforeach
```

## Editor.js Integration

Install the required npm packages:

```bash
npm install --save @editorjs/editorjs @editorjs/header @editorjs/list @editorjs/image @editorjs/quote @editorjs/table @editorjs/delimiter @editorjs/embed @editorjs/link @editorjs/raw @editorjs/simple-image @calumk/editorjs-columns
```

Or copy dependencies from `package.json` in the package root.

### Frontend Configuration

You must also configure the `window.Laravel` object in your admin layout. See the [Installation and Setup Guide](docs/installation-and-setup.md#laravel-object-configuration) for details.

## Testing

```bash
composer test
```

## Documentation

For detailed documentation, see the [docs](docs/) directory:

- [Installation and Setup](docs/installation-and-setup.md)
- [Requirements](docs/requirements.md)
- [Basic Usage](docs/basic-usage/)

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email security@javaabu.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Javaabu](https://javaabu.com)
- [All Contributors](../../contributors)
