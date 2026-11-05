<?php

namespace Javaabu\Cms\Tests\Feature;

use Javaabu\Cms\Content\Defaults;
use Javaabu\Cms\Facades\Cms;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\DynamicMenubuilder\Facades\Menus;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\Widgets\Facades\Widgets;
use PHPUnit\Framework\Attributes\Test;

class ConfigurationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('cms.post_types', ['page', Defaults::news()->slug('updates')]);
        $app['config']->set('cms.taxonomies', ['categories', 'tags']);
        $app['config']->set('cms.menu_locations', ['header' => 3, 'mobile']);
        $app['config']->set('cms.widget_locations', ['sidebar']);
        $app['config']->set('cms.widgets', []);
        $app['config']->set('cms.settings', false);
        $app['config']->set('cms.views', 'inertia');
        $app['config']->set('cms.layouts.admin', 'layouts.admin');
    }

    #[Test]
    public function definitions_can_be_changed_from_config(): void
    {
        $this->assertSame('updates', PostTypes::get('news')->getSlug());
        $this->assertSame(3, Menus::getLocation('header')->getMaxDepth());
        $this->assertTrue(Menus::hasLocation('mobile'));
        $this->assertFalse(Menus::hasLocation('footer'));
        $this->assertFalse(Widgets::hasLocation('footer'));
        $this->assertNull(Widgets::find('recent-posts'));
    }

    #[Test]
    public function the_views_and_layouts_are_applied_to_every_package(): void
    {
        foreach (['post-types', 'widgets', 'dynamic-menubuilder', 'settings', 'mediapicker', 'permissions'] as $package) {
            $this->assertSame('inertia', config("{$package}.views"), $package);
            $this->assertSame('layouts.admin', config("{$package}.layout"), $package);
            $this->assertSame('content', config("{$package}.layout_section"), $package);
        }

        $this->assertSame('cms::layouts.public', config('post-types.public_layout'));
    }

    #[Test]
    public function without_site_settings_the_site_name_is_the_app_name(): void
    {
        $this->assertSame('Javaabu', Cms::siteName());
        $this->assertNull(Cms::tagline());
        $this->assertNull(Cms::frontPage());
    }
}
