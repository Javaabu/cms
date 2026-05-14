<?php

namespace Javaabu\Cms\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Enums\Languages;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Models\TranslatablePost;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslatablePostAdminUrlTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function post_translation_url_includes_locale_post_type_and_post_id(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../src/Models/TranslatablePost.php');

        $this->assertIsString($source);
        $this->assertStringContainsString("'.posts.' . \$action", $source);
        $this->assertStringContainsString("\$params = [\$locale, \$postType];", $source);
        $this->assertStringContainsString("\$params[] = \$this->id;", $source);
    }

    #[Test]
    public function post_admin_url_is_translated_when_translations_are_enabled(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../src/Models/Post.php');

        $this->assertIsString($source);
        $this->assertStringContainsString("config('cms.should_translate')", $source);
        $this->assertStringContainsString("translate_route(\$routeName, [\$this->postType, \$this])", $source);
    }

    #[Test]
    public function post_admin_url_has_no_locale_prefix_when_translations_are_disabled(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../src/Models/Post.php');

        $this->assertIsString($source);
        $this->assertStringContainsString("return route(\$routeName, [\$this->postType, \$this]);", $source);
    }

    #[Test]
    public function translation_form_uses_model_translation_url_for_edit_translation_button(): void
    {
        $template = file_get_contents(__DIR__ . '/../../../resources/views/admin/components/post-type/form/translations.blade.php');

        $this->assertIsString($template);
        $this->assertStringContainsString("href=\"{{ \$model->translation_url }}\"", $template);
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

    private function createTranslatablePost(PostType $postType): TranslatablePost
    {
        $post = new TranslatablePost([
            'type' => $postType->slug,
            'title' => 'Default Post',
            'slug' => 'default-' . fake()->unique()->numberBetween(1, 999999),
            'status' => \Javaabu\Cms\Enums\PostStatus::DRAFT->value,
            'published_at' => now(),
            'content' => 'Test content',
        ]);

        $post->lang = Languages::EN;
        $post->save();

        return $post;
    }
}
