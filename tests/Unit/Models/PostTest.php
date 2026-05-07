<?php

namespace Javaabu\Cms\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private function create_post_type(array $attributes = []): PostType
    {
        $post_type = new PostType(array_merge([
            'name' => 'Default Type',
            'singular_name' => 'Default Item',
            'slug' => 'default-type',
            'icon' => 'ri-file-line',
        ], $attributes));

        $post_type->lang = 'en';
        $post_type->save();

        return $post_type;
    }

    private function create_post(PostType $post_type, array $attributes = []): Post
    {
        $post = new Post(array_merge([
            'type' => $post_type->slug,
            'title' => 'Default Post',
            'slug' => 'default-post',
            'status' => PostStatus::DRAFT->value,
        ], $attributes));

        $post->lang = 'en';
        $post->save();

        return $post;
    }

    #[Test]
    public function it_sets_slug_when_creating_a_post_with_published_at(): void
    {
        Carbon::setTestNow('2026-05-04 10:00:00');

        $post_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $post = $this->create_post($post_type, [
            'title' => 'Breaking Story',
            'slug' => 'Breaking Story',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $this->assertSame('breaking-story', $post->slug);
        $this->assertTrue($post->published_at->equalTo(now()));
    }

    #[Test]
    public function it_requires_published_at_when_creating_a_post(): void
    {
        $this->expectException(QueryException::class);

        $post_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $post = new Post([
            'type' => $post_type->slug,
            'title' => 'Missing Published At',
            'slug' => 'missing-published-at',
            'status' => PostStatus::DRAFT->value,
        ]);

        $post->lang = 'en';
        $post->save();
    }

    #[Test]
    public function it_returns_published_posts_in_published_scope(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Updates',
            'singular_name' => 'Update',
            'slug' => 'updates',
            'icon' => 'ri-refresh-line',
        ]);

        $published_post = $this->create_post($post_type, [
            'title' => 'Published Post',
            'slug' => 'published-post',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $ids = Post::published()->pluck('id')->all();

        $this->assertSame([$published_post->id], $ids);
    }

    #[Test]
    public function it_excludes_non_published_statuses_from_published_scope(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Updates',
            'singular_name' => 'Update',
            'slug' => 'updates',
            'icon' => 'ri-refresh-line',
        ]);

        $this->create_post($post_type, [
            'title' => 'Published Post',
            'slug' => 'published-post',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $this->create_post($post_type, [
            'title' => 'Scheduled Post',
            'slug' => 'scheduled-post',
            'status' => PostStatus::SCHEDULED->value,
            'published_at' => now()->addDay(),
        ]);

        $this->create_post($post_type, [
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now()->subDay(),
        ]);

        $ids = Post::published()->pluck('id')->all();

        $this->assertSame([
            Post::query()->where('slug', 'published-post')->value('id'),
        ], $ids);
    }

    #[Test]
    public function it_excludes_future_published_at_posts_from_published_scope(): void
    {
        Carbon::setTestNow('2026-05-04 10:00:00');

        $post_type = $this->create_post_type([
            'name' => 'Updates',
            'singular_name' => 'Update',
            'slug' => 'updates',
            'icon' => 'ri-refresh-line',
        ]);

        $included_post = $this->create_post($post_type, [
            'title' => 'Already Published',
            'slug' => 'already-published',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subMinute(),
        ]);

        $this->create_post($post_type, [
            'title' => 'Future Published',
            'slug' => 'future-published',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->addMinute(),
        ]);

        $ids = Post::published()->pluck('id')->all();

        $this->assertSame([$included_post->id], $ids);
    }

    #[Test]
    public function it_returns_only_future_scheduled_posts_in_scheduled_scope(): void
    {
        Carbon::setTestNow('2026-05-04 10:00:00');

        $post_type = $this->create_post_type([
            'name' => 'Updates',
            'singular_name' => 'Update',
            'slug' => 'updates',
            'icon' => 'ri-refresh-line',
        ]);

        $scheduled = $this->create_post($post_type, [
            'title' => 'Scheduled',
            'slug' => 'scheduled',
            'status' => PostStatus::SCHEDULED->value,
            'published_at' => now()->addHour(),
        ]);

        $this->create_post($post_type, [
            'title' => 'Scheduled In Past',
            'slug' => 'scheduled-in-past',
            'status' => PostStatus::SCHEDULED->value,
            'published_at' => now()->subHour(),
        ]);

        $this->create_post($post_type, [
            'title' => 'Published Future',
            'slug' => 'published-future',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->addHour(),
        ]);

        $ids = Post::scheduled()->pluck('id')->all();

        $this->assertSame([$scheduled->id], $ids);
    }

    #[Test]
    public function it_filters_posts_by_published_year(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Updates',
            'singular_name' => 'Update',
            'slug' => 'updates',
            'icon' => 'ri-refresh-line',
        ]);

        $in_2026 = $this->create_post($post_type, [
            'title' => 'In 2026',
            'slug' => 'in-2026',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => '2026-04-01 10:00:00',
        ]);

        $this->create_post($post_type, [
            'title' => 'In 2025',
            'slug' => 'in-2025',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => '2025-04-01 10:00:00',
        ]);

        $ids = Post::publishedByYear(2026)->pluck('id')->all();

        $this->assertSame([$in_2026->id], $ids);
    }

    #[Test]
    public function it_updates_to_published_status_when_publish_action_is_called(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Articles',
            'singular_name' => 'Article',
            'slug' => 'articles',
            'icon' => 'ri-article-line',
        ]);

        $post = $this->create_post($post_type, [
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now()->subHour(),
        ]);

        $post->publish();
        $post->refresh();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => PostStatus::PUBLISHED->value,
        ]);
        $this->assertTrue($post->isPublished());
    }

    #[Test]
    public function it_marks_post_as_published_only_when_published_at_is_not_in_future(): void
    {
        Carbon::setTestNow('2026-05-04 10:00:00');

        $post_type = $this->create_post_type([
            'name' => 'Articles',
            'singular_name' => 'Article',
            'slug' => 'articles',
            'icon' => 'ri-article-line',
        ]);

        $published_now = $this->create_post($post_type, [
            'title' => 'Publish Now',
            'slug' => 'publish-now',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);

        $published_past = $this->create_post($post_type, [
            'title' => 'Publish Past',
            'slug' => 'publish-past',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subSecond(),
        ]);

        $published_future = $this->create_post($post_type, [
            'title' => 'Publish Future',
            'slug' => 'publish-future',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->addSecond(),
        ]);

        $this->assertTrue($published_now->isPublished());
        $this->assertTrue($published_past->isPublished());
        $this->assertFalse($published_future->isPublished());
    }

    #[Test]
    public function it_sets_published_at_to_now_when_publish_is_called_and_published_at_is_missing(): void
    {
        Carbon::setTestNow('2026-05-04 10:00:00');

        $post_type = $this->create_post_type([
            'name' => 'Articles',
            'singular_name' => 'Article',
            'slug' => 'articles',
            'icon' => 'ri-article-line',
        ]);

        $post = $this->create_post($post_type, [
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now()->subHour(),
        ]);

        $post->setRawAttributes(array_merge($post->getAttributes(), [
            'published_at' => null,
        ]), true);

        $this->assertNull($post->published_at);

        $post->publish();
        $post->refresh();

        $this->assertSame(PostStatus::PUBLISHED->value, $post->status);
        $this->assertTrue($post->published_at->equalTo(now()));
    }

    #[Test]
    public function it_keeps_existing_published_at_when_publish_is_called(): void
    {
        Carbon::setTestNow('2026-05-04 10:00:00');

        $post_type = $this->create_post_type([
            'name' => 'Articles',
            'singular_name' => 'Article',
            'slug' => 'articles',
            'icon' => 'ri-article-line',
        ]);

        $existing_published_at = now()->subDay();

        $post = $this->create_post($post_type, [
            'title' => 'Draft Article',
            'slug' => 'draft-article-existing-date',
            'status' => PostStatus::DRAFT->value,
            'published_at' => $existing_published_at,
        ]);

        $post->publish();
        $post->refresh();

        $this->assertSame(PostStatus::PUBLISHED->value, $post->status);
        $this->assertTrue($post->published_at->equalTo($existing_published_at));
    }

    #[Test]
    public function it_sets_published_at_when_update_status_is_called_with_publish_true_and_no_published_at(): void
    {
        Carbon::setTestNow('2026-05-04 10:00:00');

        $post_type = $this->create_post_type([
            'name' => 'Articles',
            'singular_name' => 'Article',
            'slug' => 'articles',
            'icon' => 'ri-article-line',
        ]);

        $post = $this->create_post($post_type, [
            'title' => 'Draft Article',
            'slug' => 'draft-article-for-update-status',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now()->subHour(),
        ]);

        $post->setRawAttributes(array_merge($post->getAttributes(), [
            'published_at' => null,
        ]), true);

        $this->assertNull($post->published_at);

        $post->updateStatus(PostStatus::PUBLISHED->value, true);
        $post->refresh();

        $this->assertSame(PostStatus::PUBLISHED->value, $post->status);
        $this->assertTrue($post->published_at->equalTo(now()));
    }

    #[Test]
    public function it_keeps_existing_published_at_when_update_status_is_called_without_publish_flag(): void
    {
        Carbon::setTestNow('2026-05-04 10:00:00');

        $post_type = $this->create_post_type([
            'name' => 'Articles',
            'singular_name' => 'Article',
            'slug' => 'articles',
            'icon' => 'ri-article-line',
        ]);

        $existing_published_at = now()->subHour();

        $post = $this->create_post($post_type, [
            'title' => 'Draft Article',
            'slug' => 'draft-article-no-publish-flag',
            'status' => PostStatus::DRAFT->value,
            'published_at' => $existing_published_at,
        ]);

        $post->updateStatus(PostStatus::PUBLISHED->value, false);
        $post->refresh();

        $this->assertSame(PostStatus::PUBLISHED->value, $post->status);
        $this->assertTrue($post->published_at->equalTo($existing_published_at));
    }

    #[Test]
    public function it_keeps_existing_published_at_when_update_status_is_called_with_publish_true(): void
    {
        Carbon::setTestNow('2026-05-04 10:00:00');

        $post_type = $this->create_post_type([
            'name' => 'Articles',
            'singular_name' => 'Article',
            'slug' => 'articles',
            'icon' => 'ri-article-line',
        ]);

        $existing_published_at = now()->subDay();

        $post = $this->create_post($post_type, [
            'title' => 'Draft Article',
            'slug' => 'draft-article-existing-date-update-status',
            'status' => PostStatus::DRAFT->value,
            'published_at' => $existing_published_at,
        ]);

        $post->updateStatus(PostStatus::PUBLISHED->value, true);
        $post->refresh();

        $this->assertSame(PostStatus::PUBLISHED->value, $post->status);
        $this->assertTrue($post->published_at->equalTo($existing_published_at));
    }

    #[Test]
    public function it_updates_to_rejected_status_when_reject_action_is_called(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Blogs',
            'singular_name' => 'Blog',
            'slug' => 'blogs',
            'icon' => 'ri-quill-pen-line',
        ]);

        $post = $this->create_post($post_type, [
            'title' => 'Pending Blog',
            'slug' => 'pending-blog',
            'status' => PostStatus::PENDING->value,
            'published_at' => now(),
        ]);

        $post->reject();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => PostStatus::REJECTED->value,
        ]);
    }

    #[Test]
    public function it_rejects_duplicate_slug_within_same_post_type(): void
    {
        $news_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $events_type = $this->create_post_type([
            'name' => 'Events',
            'singular_name' => 'Event',
            'slug' => 'events',
            'icon' => 'ri-calendar-event-line',
        ]);

        $this->create_post($news_type, [
            'title' => 'Existing Post',
            'slug' => 'shared-slug',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $post_in_same_type = new Post([
            'type' => $news_type->slug,
            'title' => 'Another News Post',
            'slug' => 'new-slug',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $this->assertFalse($post_in_same_type->isUniqueSlug('shared-slug'));
    }

    #[Test]
    public function it_allows_same_slug_in_different_post_type(): void
    {
        $news_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $events_type = $this->create_post_type([
            'name' => 'Events',
            'singular_name' => 'Event',
            'slug' => 'events',
            'icon' => 'ri-calendar-event-line',
        ]);

        $this->create_post($news_type, [
            'title' => 'Existing Post',
            'slug' => 'shared-slug',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $post_in_other_type = new Post([
            'type' => $events_type->slug,
            'title' => 'Event Post',
            'slug' => 'new-event-slug',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $this->assertTrue($post_in_other_type->isUniqueSlug('shared-slug'));
    }

    #[Test]
    public function it_allows_same_slug_for_the_same_post_instance_on_update(): void
    {
        $news_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $post = $this->create_post($news_type, [
            'title' => 'Existing Post',
            'slug' => 'shared-slug',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $this->assertTrue($post->isUniqueSlug('shared-slug'));
    }

    #[Test]
    public function it_rejects_reusing_slug_from_soft_deleted_post_in_same_type(): void
    {
        $news_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $post = $this->create_post($news_type, [
            'title' => 'Existing Post',
            'slug' => 'shared-slug',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $post->delete();

        $new_post = new Post([
            'type' => $news_type->slug,
            'title' => 'New Post',
            'slug' => 'new-post',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $this->assertFalse($new_post->isUniqueSlug('shared-slug'));
    }

    #[Test]
    public function it_auto_suffixes_duplicate_slug_within_same_type_before_save(): void
    {
        $news_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $this->create_post($news_type, [
            'title' => 'Existing Post',
            'slug' => 'shared-slug',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $duplicate_post = new Post([
            'type' => $news_type->slug,
            'title' => 'Duplicate Post',
            'slug' => 'shared-slug',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $duplicate_post->lang = 'en';
        $duplicate_post->save();

        $this->assertSame('shared-slug-1', $duplicate_post->slug);
    }

    #[Test]
    public function it_casts_published_at_string_to_datetime(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $post = $this->create_post($post_type, [
            'title' => 'String Date Post',
            'slug' => 'string-date-post',
            'status' => PostStatus::DRAFT->value,
            'published_at' => '2026-05-04 11:30:00',
        ]);

        $this->assertSame('2026-05-04 11:30:00', $post->published_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_orders_posts_by_menu_order_then_published_at_desc(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $first = $this->create_post($post_type, [
            'title' => 'First',
            'slug' => 'first',
            'status' => PostStatus::DRAFT->value,
            'menu_order' => 1,
            'published_at' => now()->subDay(),
        ]);

        $second = $this->create_post($post_type, [
            'title' => 'Second',
            'slug' => 'second',
            'status' => PostStatus::DRAFT->value,
            'menu_order' => 1,
            'published_at' => now(),
        ]);

        $third = $this->create_post($post_type, [
            'title' => 'Third',
            'slug' => 'third',
            'status' => PostStatus::DRAFT->value,
            'menu_order' => 2,
            'published_at' => now()->addDay(),
        ]);

        $ordered_ids = Post::ordered()->pluck('id')->all();

        $this->assertSame([$second->id, $first->id, $third->id], $ordered_ids);
    }
}
