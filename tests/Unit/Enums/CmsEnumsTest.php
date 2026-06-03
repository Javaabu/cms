<?php

namespace Javaabu\Cms\Tests\Unit\Enums;

use Javaabu\Cms\Enums\GalleryTypes;
use Javaabu\Cms\Enums\Languages;
use Javaabu\Cms\Enums\PageStyles;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Models\TranslatableTag;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ValueError;

class CmsEnumsTest extends TestCase
{
    #[Test]
    public function page_styles_expose_stable_labels(): void
    {
        $this->assertSame([
            'fullwidth' => 'Fullwidth',
            'sidebar' => 'Sidebar',
        ], PageStyles::labels());
    }

    #[Test]
    public function gallery_types_map_admin_and_web_icon_classes(): void
    {
        $this->assertSame('zmdi zmdi-image', GalleryTypes::getIcon(GalleryTypes::PHOTO->value));
        $this->assertSame('zmdi zmdi-videocam', GalleryTypes::getIcon(GalleryTypes::VIDEO->value));
        $this->assertSame('far fa-image', GalleryTypes::getWebIcon(GalleryTypes::PHOTO->value));
        $this->assertSame('far fa-play', GalleryTypes::getWebIcon(GalleryTypes::VIDEO->value));
        $this->assertSame('', GalleryTypes::getIcon('unknown'));
        $this->assertSame('', GalleryTypes::getWebIcon('unknown'));
    }

    #[Test]
    public function languages_resolve_locale_direction_opposites_and_validity(): void
    {
        config()->set('app.fallback_locale', 'en');
        config()->set('translations.default_translation_locale', 'dv');
        app()->setLocale('dv');

        $this->assertSame('en', Languages::getDefaultAppLocale());
        $this->assertSame('dv', Languages::getDefaultTranslationLocale());
        $this->assertSame('en', Languages::getOppositeLocale());
        $this->assertSame('dv', Languages::getOppositeLocale('en'));
        $this->assertSame('rtl', Languages::getDirection());
        $this->assertSame('ltr', Languages::getDirection('en'));
        $this->assertTrue(Languages::isRtl('dv'));
        $this->assertFalse(Languages::isRtl('en'));
        $this->assertTrue(Languages::isValidKey('en'));
        $this->assertTrue(Languages::isValidKey('dv'));
        $this->assertFalse(Languages::isValidKey('fr'));
    }

    #[Test]
    public function languages_build_localized_urls_for_strings_and_fallbacks(): void
    {
        config()->set('app.url', 'http://example.test');
        config()->set('app.admin_domain', 'admin.example.test');
        app()->setLocale('en');

        $this->assertSame(public_url('/dv/news'), Languages::getLocalizedUrl('news'));
        $this->assertSame(public_url('/dv/news'), Languages::getLocalizedUrl('/news'));
        $this->assertSame(public_url('/dv'), Languages::getLocalizedUrl(null));
        $this->assertSame(admin_url('/dv/news'), Languages::getAdminLocalizedUrl('news'));
        $this->assertSame(admin_url('/dv'), Languages::getAdminLocalizedUrl(null));
    }

    #[Test]
    public function translatable_models_expose_allowed_locale_helpers(): void
    {
        config()->set('translations.default_translation_locale', 'en');

        $tag = new TranslatableTag(['name' => 'Policy Update']);

        $this->assertSame('en', $tag->getDefaultTranslationLocale());
        $this->assertSame(['en', 'dv'], $tag->getAllowedTranslationLocales());
        $this->assertTrue($tag->isDefaultTranslationLocale('EN'));
        $this->assertTrue($tag->isAllowedTranslationLocale('fr'));
        $this->assertFalse($tag->isNonTranslatablePivot('categories'));
        $this->assertFalse($tag->isNonTranslatableAttachmentCollection('documents'));
    }

    #[Test]
    public function post_type_features_expose_labels_collection_names_and_dummy_data(): void
    {
        $this->assertSame('Documents', PostTypeFeatures::DOCUMENTS->label());
        $this->assertSame('Image Gallery', PostTypeFeatures::IMAGE_GALLERY->label());
        $this->assertSame('Root Page', PostTypeFeatures::ROOT_PAGE->label());
        $this->assertSame('Redirect URL', PostTypeFeatures::REDIRECT_URL->label());

        $this->assertSame('documents', PostTypeFeatures::DOCUMENTS->getCollectionName());
        $this->assertSame('documents_translated', PostTypeFeatures::DOCUMENTS->getCollectionName(true));
        $this->assertSame('image_gallery', PostTypeFeatures::IMAGE_GALLERY->getCollectionName());
        $this->assertSame('image_gallery', PostTypeFeatures::FORMAT->getCollectionName());
        $this->assertSame('', PostTypeFeatures::VIDEO_LINK->getCollectionName());

        $this->assertSame('https://i.imgur.com/KDezgrt.jpg', PostTypeFeatures::DOCUMENTS->getDummyData());
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', PostTypeFeatures::VIDEO_LINK->getDummyData());
        $this->assertSame('', PostTypeFeatures::ROOT_PAGE->getDummyData());
    }

    #[Test]
    public function post_type_features_can_be_resolved_from_case_insensitive_labels(): void
    {
        foreach (PostTypeFeatures::cases() as $feature) {
            $this->assertSame($feature, PostTypeFeatures::fromLabel('  ' . strtolower($feature->label()) . '  '));
        }
    }

    #[Test]
    public function post_type_features_throw_for_unknown_labels(): void
    {
        $this->expectException(ValueError::class);

        PostTypeFeatures::fromLabel('Not a feature');
    }

}
