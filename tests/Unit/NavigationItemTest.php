<?php

namespace Javaabu\Cms\Tests\Unit;

use Javaabu\Cms\Navigation\Navigation;
use Javaabu\Cms\Navigation\NavigationItem;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NavigationItemTest extends TestCase
{
    #[Test]
    public function the_label_defaults_to_the_key_and_is_translated(): void
    {
        $this->assertSame('Site Reports', NavigationItem::make('site-reports')->getLabel());
        $this->assertSame('ރިޕޯޓު', NavigationItem::make('reports')->label(fn () => 'ރިޕޯޓު')->getLabel());
    }

    #[Test]
    public function a_route_link_is_active_on_its_resource(): void
    {
        $item = NavigationItem::make('news')->route('news.index');

        $this->assertTrue($item->isActive('admin.news.edit', 'admin.'));
        $this->assertFalse($item->isActive('admin.newsletters.index', 'admin.'));
        $this->assertFalse($item->isActive(null, 'admin.'));

        $dashboard = NavigationItem::make('dashboard')->route('dashboard');

        $this->assertTrue($dashboard->isActive('admin.dashboard', 'admin.'));
        $this->assertFalse($dashboard->isActive('admin.news.index', 'admin.'));

        $custom = NavigationItem::make('stats')->route('reports.stats', relative: false)->active(['reports.*']);

        $this->assertTrue($custom->isActive('reports.visits', 'admin.'));
    }

    #[Test]
    public function a_link_without_a_url_or_children_is_hidden(): void
    {
        $this->assertNull(NavigationItem::make('nothing')->resolve(null));
        $this->assertNotNull(NavigationItem::make('docs')->url('https://docs.javaabu.com')->resolve(null));
        $this->assertNull(NavigationItem::make('private')->url('/x')->can('view_reports')->resolve(null));
    }

    #[Test]
    public function items_sort_by_order_then_by_when_they_were_added(): void
    {
        $sorted = Navigation::sortItems([
            NavigationItem::make('c')->order(5),
            NavigationItem::make('a'),
            NavigationItem::make('b'),
            NavigationItem::make('first')->order(1),
        ]);

        $this->assertSame(['first', 'c', 'a', 'b'], array_map(fn (NavigationItem $item) => $item->getKey(), $sorted));
    }
}
