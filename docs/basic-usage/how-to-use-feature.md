---
title: Working with Post Types
---

This guide explains how to create, configure, and manage custom post types in Javaabu CMS.

## What are Post Types?

Post Types define the structure and features of your content. Each post type can have different features like categories, featured images, documents, video links, and more.

## Creating Post Types

### Via Database Seeder

Create post types programmatically in your database seeder:

```php
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Models\CategoryType;

// First, create a category type
$categoryType = CategoryType::create([
    'name' => 'News Categories',
    'singular_name' => 'News Category',
    'slug' => 'news-categories',
]);

// Then create the post type
$postType = PostType::create([
    'name' => 'News Articles',
    'singular_name' => 'News Article',
    'slug' => 'news',
    'icon' => 'zmdi-newspaper',
    'category_type_id' => $categoryType->id,
    'features' => [
        'categories' => true,
        'featured_image' => true,
        'excerpt' => true,
        'documents' => true,
        'document_number' => 'Document No.',
        'expireable' => true,
    ],
    'description' => 'Latest news and announcements',
    'og_description' => 'Stay updated with our latest news',
    'order_column' => 0,
]);
```

### Via Admin Panel

1. Navigate to `/admin/post-types`
2. Click "Add New"
3. Fill in the form:
   - **Name**: Plural name (e.g., "News Articles")
   - **Singular Name**: Singular name (e.g., "News Article")
   - **Slug**: URL-friendly identifier (e.g., "news")
   - **Icon**: Material Design icon class (e.g., "zmdi-newspaper")
   - **Category Type**: Select associated category type
   - **Features**: Check desired features
4. Click "Save"

## Available Post Type Features

Configure which features are available for each post type:

### Core Features

- **categories**: Enable category assignment
- **featured_image**: Featured/thumbnail image
- **excerpt**: Short summary/description

### Document Features

- **documents**: Attach PDF/document files
- **document_number**: Document reference number field

### Media Features

- **image_gallery**: Multiple image upload
- **video_link**: YouTube/Vimeo embed URL
- **format**: Content format (photo, video, gallery)

### Advanced Features

- **expireable**: Set expiration date
- **page_style**: Custom page styling options
- **sidebar_menu**: Assign sidebar navigation
- **ref_no**: Reference number field
- **gazette_link**: Link to gazette document
- **recently_updated**: Flag for recent updates

## Configuring Features

### Basic Configuration

```php
$postType->features = [
    'categories' => true,
    'featured_image' => true,
    'excerpt' => true,
];
```

### Custom Feature Labels

You can customize feature labels:

```php
$postType->features = [
    'document_number' => 'Ref No.',  // Custom label
    'expireable' => 'Valid Until',   // Custom label
];
```

## Checking Features

Check if a post type has a specific feature:

```php
if ($postType->hasFeature('categories')) {
    // Show category selector
}

if ($postType->hasFeature('document_number')) {
    $label = $postType->getFeatureName('document_number');
    // Display: "Ref No." or default "Document Number"
}
```

## Post Type Methods

### Useful Methods

```php
// Get all posts of this type
$posts = $postType->posts()->get();

// Get published posts
$posts = $postType->posts()->published()->get();

// Get categories for this post type
$categories = $postType->categoriesFor();

// Get permission slug
$permission = $postType->permission_slug;  // e.g., "news"

// Get lowercase names
$name = $postType->lower_name;  // e.g., "news articles"
$singularName = $postType->lower_singular_name;  // e.g., "news article"

// Get pagination count
$perPage = $postType->getPaginatorCount();  // From config
```

## Associating with Categories

Link a post type to a category type:

```php
$categoryType = CategoryType::where('slug', 'news-categories')->first();

$postType->category_type_id = $categoryType->id;
$postType->save();
```

## Registering Routes

Register routes for a specific post type:

```php
use Javaabu\Cms\Support\Routes;

// In routes/web.php
Routes::customPostType(
    postTypeSlug: 'news',
    prefix: 'news',
    middleware: ['web']
);
```

This creates:
- `/news` - List all news posts
- `/news/{slug}` - View single news post
- `/news/category/{category}` - Filter by category

## Example: Blog Post Type

Complete example for a blog:

```php
// 1. Create category type
$blogCategories = CategoryType::create([
    'name' => 'Blog Categories',
    'singular_name' => 'Blog Category',
    'slug' => 'blog-categories',
]);

// 2. Create post type
$blog = PostType::create([
    'name' => 'Blog Posts',
    'singular_name' => 'Blog Post',
    'slug' => 'blog',
    'icon' => 'zmdi-comment-text',
    'category_type_id' => $blogCategories->id,
    'features' => [
        'categories' => true,
        'featured_image' => true,
        'excerpt' => true,
        'video_link' => true,
        'image_gallery' => true,
    ],
    'description' => 'Company blog posts',
    'order_column' => 0,
]);

// 3. Register routes
Routes::customPostType('blog', prefix: 'blog');
```

## Best Practices

1. **Use descriptive slugs**: Keep them short and URL-friendly
2. **Enable only needed features**: Reduces form complexity
3. **Set proper icons**: Use Material Design icon classes
4. **Configure category types**: Link related category types
5. **Order logically**: Use `order_column` for admin menu order
6. **Add descriptions**: Help content editors understand the purpose

## Next Steps

- [Managing Categories](managing-categories.md)
- [Creating Posts](creating-posts.md)
- [Querying Posts](querying-posts.md)
