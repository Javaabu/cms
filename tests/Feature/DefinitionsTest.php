<?php

namespace Javaabu\Cms\Tests\Feature;

use Javaabu\Cms\Content\Defaults;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Widgets\RecentPostsWidget;
use Javaabu\Cms\Widgets\TermsWidget;
use Javaabu\DynamicMenubuilder\Facades\Menus;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Widgets\Facades\Widgets;
use PHPUnit\Framework\Attributes\Test;

class DefinitionsTest extends TestCase
{
    #[Test]
    public function it_registers_pages_and_news_with_categories_and_tags(): void
    {
        $page = PostTypes::get('page');
        $news = PostTypes::get('news');

        $this->assertSame('', $page->getSlug());
        $this->assertSame('{slug}', $page->getPermalinkStructure());
        $this->assertFalse($page->hasArchive());
        $this->assertSame([], $page->getTaxonomyKeys());

        $this->assertSame('news', $news->getSlug());
        $this->assertSame('{year}/{month}/{slug}', $news->getPermalinkStructure());
        $this->assertTrue($news->hasArchive());
        $this->assertSame(['categories', 'tags'], $news->getTaxonomyKeys());
        $this->assertTrue($news->supportsFeature(PostType::SCHEDULING));

        $this->assertTrue(PostTypes::getTaxonomy('categories')->isHierarchical());
        $this->assertFalse(PostTypes::getTaxonomy('tags')->isHierarchical());
    }

    #[Test]
    public function it_registers_menu_locations_widget_locations_and_widgets(): void
    {
        $this->assertTrue(Menus::hasLocation('header'));
        $this->assertSame(2, Menus::getLocation('header')->getMaxDepth());
        $this->assertSame(1, Menus::getLocation('footer')->getMaxDepth());

        $this->assertTrue(Widgets::hasLocation('sidebar'));
        $this->assertTrue(Widgets::hasLocation('footer'));
        $this->assertSame('Sidebar', Widgets::findLocation('sidebar')->getLabel());

        $this->assertInstanceOf(RecentPostsWidget::class, Widgets::find('recent-posts'));
        $this->assertInstanceOf(TermsWidget::class, Widgets::find('terms'));
    }

    #[Test]
    public function it_registers_the_site_settings_page(): void
    {
        $this->assertNotNull(Settings::definition('cms_site_name'));
        $this->assertSame('Javaabu', settings('cms_site_name'));
    }

    #[Test]
    public function it_gives_the_defaults_to_start_from(): void
    {
        $this->assertSame('page', Defaults::postType('page')->getKey());
        $this->assertNull(Defaults::postType('events'));
        $this->assertSame('tags', Defaults::taxonomy('tags')->getKey());
        $this->assertNull(Defaults::taxonomy('regions'));
    }
}
