<?php

namespace Javaabu\Cms\Tests\Http;

use Illuminate\Support\Facades\File;
use Javaabu\Cms\Facades\Cms;
use Javaabu\Cms\Support\Translations;
use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\Concerns\InteractsWithTranslatable;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\DynamicMenubuilder\Facades\Menus;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Translatable\Post as TranslatablePost;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Translatable\Facades\Languages;
use Javaabu\Translatable\Translatable;
use Javaabu\Widgets\Facades\Widgets;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

#[RequiresMethod(Translatable::class, 'enabled')]
class TranslatableTest extends TestCase
{
    use InteractsWithCms;
    use InteractsWithTranslatable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLanguages(['en', 'dv']);
    }

    protected function tearDown(): void
    {
        File::delete(config_path('cms.php'));

        parent::tearDown();
    }

    #[Test]
    public function the_site_has_a_language_switcher(): void
    {
        $this->assertTrue(Translations::enabled());

        $site = Cms::site();

        $this->assertSame(['en', 'dv'], array_column($site['locales'], 'code'));
        $this->assertSame([true, false], array_column($site['locales'], 'current'));
        $this->assertSame('ltr', $site['direction']);

        Languages::setCurrentLocale('dv');

        $this->assertSame('rtl', Cms::site()['direction']);
        $this->assertSame('dv', Cms::site()['locale']);
    }

    #[Test]
    public function the_theme_switches_language_and_direction(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('class="cms-languages"', false)
            ->assertSee('hreflang="dv"', false)
            ->assertSee('ދިވެހި');

        $this->get('/?lang=dv')
            ->assertOk()
            ->assertSee('dir="rtl"', false);
    }

    #[Test]
    public function the_site_name_is_translatable(): void
    {
        Settings::set(['cms_site_name' => ['en' => 'Javaabu', 'dv' => 'ޖާވާބު']]);

        $this->assertSame('Javaabu', Cms::siteName());

        Languages::setCurrentLocale('dv');

        $this->assertSame('ޖާވާބު', Cms::siteName());
    }

    #[Test]
    public function the_starter_content_is_in_both_languages(): void
    {
        $this->artisan('cms:install')->assertSuccessful();

        $about = PostTypes::get('page')->query()->where('slug', 'about')->first();

        $this->assertInstanceOf(TranslatablePost::class, $about);
        $this->assertSame('About us', $about->title);

        $widget = Widgets::widgets('sidebar')->first();

        $this->assertSame(['en' => 'Latest news', 'dv' => 'އެންމެ ފަހުގެ ޚަބަރު'], $widget->settings['title']);
        $this->assertSame('Home', Menus::items('header')[0]->title);

        Languages::setCurrentLocale('dv');
        Menus::flush();
        Widgets::flush();

        $this->assertSame('އަޅުގަނޑުމެންނާ ބެހޭ', $about->fresh()->title);
        $this->assertSame('ފުރަތަމަ ސަފްހާ', Menus::items('header')[0]->title);
        $this->assertSame('އެންމެ ފަހުގެ ޚަބަރު', Widgets::widgets('sidebar')->first()->setting('title'));
    }

    #[Test]
    public function translations_can_be_turned_off_for_the_cms(): void
    {
        config(['cms.translatable' => false]);

        $this->assertFalse(Translations::enabled());
        $this->assertSame([], Cms::site()['locales']);
        $this->get('/')->assertOk()->assertDontSee('class="cms-languages"', false);
    }
}
