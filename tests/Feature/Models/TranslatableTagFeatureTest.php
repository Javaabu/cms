<?php

namespace Javaabu\Cms\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Enums\Languages;
use Javaabu\Cms\Models\TranslatableTag;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslatableTagFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_and_writes_json_translated_attributes_by_current_locale(): void
    {
        app()->setLocale('en');

        $tag = new TranslatableTag(['name' => 'Policy Update']);
        $tag->lang = Languages::EN;
        $tag->name_dv = 'Translated Policy Update';
        $tag->save();

        $tag->refresh();

        $this->assertSame(['name' => 'Translated Policy Update'], $tag->translations);
        $this->assertSame('Policy Update', $tag->name);
        $this->assertSame('Policy Update', $tag->name_en);
        $this->assertSame('Translated Policy Update', $tag->name_dv);

        app()->setLocale('dv');

        $this->assertSame('Translated Policy Update', $tag->name);
        $this->assertSame('policy-update', $tag->slug);
        $this->assertTrue($tag->hasTranslations('dv'));
        $this->assertSame('dv', $tag->current_lang);
    }

    #[Test]
    public function it_can_disable_translation_fallbacks_for_missing_translations(): void
    {
        $tag = new TranslatableTag(['name' => 'Fallback Name']);
        $tag->lang = Languages::EN;
        $tag->save();

        app()->setLocale('dv');

        $this->assertSame('Fallback Name', $tag->name);

        $tag->dontShowTranslationFallbacks();

        $this->assertNull($tag->name);

        $tag->showTranslationFallbacks();

        $this->assertSame('Fallback Name', $tag->name);
    }

    #[Test]
    public function it_fills_only_translatable_fields_and_suffixed_translatable_fields(): void
    {
        app()->setLocale('en');

        $tag = new TranslatableTag();
        $tag->lang = Languages::EN;
        $tag->fillTranslations([
            'name' => 'Primary Name',
            'name_dv' => 'Translated Name',
            'created_at' => 'not translatable',
        ], 'dv');

        $this->assertSame('Translated Name', $tag->translations['name']);
        $this->assertSame('Primary Name', $tag->name);
        $this->assertSame('Translated Name', $tag->name_dv);
        $this->assertNull($tag->created_at);
    }

    #[Test]
    public function it_exposes_translation_metadata_and_can_clear_translations(): void
    {
        $tag = new TranslatableTag([
            'name' => 'Policy Update',
        ]);
        $tag->lang = Languages::EN;
        $tag->translations = ['name' => 'Translated Policy Update'];
        $tag->hide_translation = true;

        $this->assertSame(['name', 'dv'], $tag->getFieldAndLocale('name_dv'));
        $this->assertSame(['name_fr', null], $tag->getFieldAndLocale('name_fr'));
        $this->assertTrue($tag->isTranslatable('name'));
        $this->assertFalse($tag->isTranslatable('slug'));
        $this->assertTrue($tag->isTranslationHidden('dv'));
        $this->assertSame(['name_en' => ['name'], 'name_dv' => ['name']], $tag->addTranslationAppends([]));

        $tag->clearTranslations();

        $this->assertNull($tag->translations);
    }

    #[Test]
    public function it_handles_fillable_suffixed_translations_and_locale_specific_array_output(): void
    {
        config()->set('translations.default_translation_locale', 'en');
        app()->setLocale('dv');

        $tag = new TranslatableTag();
        $tag->lang = Languages::EN;
        $tag->fill([
            'name_dv' => 'Translated Name',
        ]);

        $this->assertSame(['name_en', 'name_dv'], $tag->getFillableTranslatables());
        $this->assertSame('Translated Name', $tag->getAttribute('name_dv'));
        $this->assertSame(['Translated Name', 'translated-name'], $tag->getAttribute(['name', 'slug']));
        $this->assertSame('Translated Name', $tag->attributesToArray()['name']);
        $this->assertSame('dv', $tag->current_lang);
        $this->assertTrue($tag->is_translation);
        $this->assertTrue($tag->getTranslation('dv'));
        $this->assertSame($tag, $tag->getTranslation('en'));
    }

    #[Test]
    public function it_exposes_primary_locale_and_non_translatable_field_helpers(): void
    {
        config()->set('translations.default_translation_locale', 'en');
        app()->setLocale('en');

        $tag = new TranslatableTag(['name' => 'Policy Update']);
        $tag->lang = Languages::DV;
        $tag->save();

        $this->assertTrue($tag->isPrimaryLocale('dv'));
        $this->assertFalse($tag->isPrimaryLocale('en'));
        $this->assertFalse($tag->isPrimaryLocale('fr'));
        $this->assertNotContains('name', $tag->getNonTranslatables());
        $this->assertSame($tag->getNonTranslatables(), $tag->getAllNonTranslatables());
        $this->assertSame('Policy Update', $tag->translateField('name'));
    }
}
