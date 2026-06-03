<?php

namespace Javaabu\Cms\Tests\Unit\Policies;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Policies\CategoryPolicy;
use Javaabu\Cms\Policies\CategoryTypePolicy;
use Javaabu\Cms\Policies\MediaPolicy;
use Javaabu\Cms\Policies\PostPolicy;
use Javaabu\Cms\Policies\PostTypePolicy;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PoliciesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function post_type_policy_maps_abilities_to_permission_slugs(): void
    {
        $postType = $this->createPostType('press-releases');
        $policy = new PostTypePolicy();

        $editor = new PermissionUser([
            'view_press_releases',
            'edit_press_releases',
            'delete_press_releases',
        ]);

        $publisher = new PermissionUser([
            'publish_press_releases',
            'publish_others_press_releases',
            'force_delete_press_releases',
        ]);

        $this->assertTrue($policy->viewAny($editor, $postType));
        $this->assertTrue($policy->create($editor, $postType));
        $this->assertTrue($policy->delete($editor, $postType));
        $this->assertTrue($policy->viewTrash($editor, $postType));
        $this->assertFalse($policy->forceDelete($editor, $postType));

        $this->assertTrue($policy->publish($publisher, $postType));
        $this->assertTrue($policy->publishOthers($publisher, $postType));
        $this->assertTrue($policy->restore($publisher, $postType));
        $this->assertTrue($policy->forceDelete($publisher, $postType));
    }

    #[Test]
    public function category_type_policy_maps_abilities_to_permission_slugs(): void
    {
        $categoryType = $this->createCategoryType('news-categories');
        $policy = new CategoryTypePolicy();
        $user = new PermissionUser(['view_news_categories', 'edit_news_categories']);

        $this->assertTrue($policy->viewAny($user, $categoryType));
        $this->assertTrue($policy->view($user, $categoryType));
        $this->assertTrue($policy->create($user, $categoryType));
        $this->assertTrue($policy->update($user, $categoryType));
        $this->assertTrue($policy->viewLogs($user, $categoryType));
        $this->assertFalse($policy->delete($user, $categoryType));
    }

    #[Test]
    public function post_policy_delegates_to_the_posts_post_type_and_checks_department_sensitive_actions(): void
    {
        $postType = $this->createPostType('news');
        $post = new Post([
            'type' => $postType->slug,
            'title' => 'Default Post',
            'slug' => 'default-post',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);
        $post->setRelation('postType', $postType);
        $post->department_id = 10;
        $post->setRelation('department', (object) ['id' => 10]);
        $policy = new PostPolicy();

        $departmentEditor = new PermissionUser(['create', 'update', 'publish'], allowedDepartments: [10]);
        $outsideDepartmentEditor = new PermissionUser(['create', 'update', 'publish'], allowedDepartments: [99]);

        $this->assertTrue($policy->viewAny(new PermissionUser(['viewAny']), $postType));
        $this->assertTrue($policy->create(new PermissionUser(['create']), $postType));
        $this->assertTrue($policy->editOthers($departmentEditor, $post));
        $this->assertFalse($policy->editOthers($outsideDepartmentEditor, $post));
        $this->assertTrue($policy->publish($departmentEditor, $post));
        $this->assertFalse($policy->publish($outsideDepartmentEditor, $post));
    }

    #[Test]
    public function category_policy_delegates_to_the_categories_type(): void
    {
        $categoryType = $this->createCategoryType('news-categories');
        $category = new Category([
            'name' => 'News',
            'slug' => 'news',
        ]);
        $category->type_id = $categoryType->id;
        $category->lang = 'en';
        $category->save();
        $policy = new CategoryPolicy();

        $user = new PermissionUser(['viewAny', 'view', 'create', 'update']);

        $this->assertTrue($policy->viewAny($user, $categoryType));
        $this->assertTrue($policy->view($user, $category));
        $this->assertTrue($policy->create($user, $categoryType));
        $this->assertTrue($policy->update($user, $category));
        $this->assertTrue($policy->viewLogs($user, $category));
        $this->assertFalse($policy->delete($user, $category));
    }

    #[Test]
    public function media_policy_allows_deleting_own_media_or_when_the_user_can_delete_other_users_media(): void
    {
        $policy = new MediaPolicy();
        $media = new Media([
            'model_type' => 'permission-user',
            'model_id' => 5,
        ]);

        $owner = new PermissionUser(['view_media', 'edit_media', 'delete_media']);
        $owner->id = 5;

        $otherUser = new PermissionUser(['delete_media']);
        $otherUser->id = 9;

        $manager = new PermissionUser(['delete_media', 'delete_other_users_media']);
        $manager->id = 9;

        $this->assertTrue($policy->viewAny($owner));
        $this->assertTrue($policy->create(new PermissionUser(['edit_media'])));
        $this->assertTrue($policy->delete($owner, $media));
        $this->assertFalse($policy->delete($otherUser, $media));
        $this->assertTrue($policy->delete($manager, $media));
    }

    private function createCategoryType(string $slug): CategoryType
    {
        $categoryType = new CategoryType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst($slug),
            'slug' => $slug,
        ]);

        $categoryType->lang = 'en';
        $categoryType->save();

        return $categoryType;
    }

    private function createPostType(string $slug): PostType
    {
        $postType = new PostType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst($slug),
            'slug' => $slug,
            'icon' => 'ri-file-line',
            'features' => [],
        ]);

        $postType->lang = 'en';
        $postType->save();

        return $postType;
    }

    private function createPost(PostType $postType, array $attributes = []): Post
    {
        $post = new Post(array_merge([
            'type' => $postType->slug,
            'title' => 'Default Post',
            'slug' => 'default-post',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ], $attributes));

        $post->lang = $attributes['lang'] ?? 'en';
        $post->save();

        return $post;
    }

    #[Test]
    public function post_type_policy_handles_class_level_authorization_checks(): void
    {
        $policy = new PostTypePolicy();
        $user = new PermissionUser();

        // Should return true when post_type is class string or null
        $this->assertTrue($policy->viewAny($user, PostType::class));
        $this->assertTrue($policy->viewAny($user, null));
        $this->assertTrue($policy->create($user, PostType::class));
        $this->assertTrue($policy->create($user, null));
    }

    #[Test]
    public function category_type_policy_handles_class_level_authorization_checks(): void
    {
        $policy = new CategoryTypePolicy();
        $user = new PermissionUser();

        // Should return true when category_type is class string or null
        $this->assertTrue($policy->viewAny($user, CategoryType::class));
        $this->assertTrue($policy->viewAny($user, null));
        $this->assertTrue($policy->create($user, CategoryType::class));
        $this->assertTrue($policy->create($user, null));
    }
}

class PermissionUser extends User
{
    public array $permissions;

    public array $allowedDepartments;

    public function __construct(array $permissions = [], array $allowedDepartments = [])
    {
        parent::__construct();

        $this->permissions = $permissions;
        $this->allowedDepartments = $allowedDepartments;
    }

    public function can($abilities, $arguments = []): bool
    {
        return in_array($abilities, $this->permissions, true);
    }

    public function isAllowedDepartment($department): bool
    {
        return in_array($department?->id, $this->allowedDepartments, true);
    }

    public function getMorphClass()
    {
        return 'permission-user';
    }
}
