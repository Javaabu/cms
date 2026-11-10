<?php

namespace Javaabu\Cms\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Javaabu\Cms\Support\Translations;
use Javaabu\Cms\Widgets\RecentPostsWidget;
use Javaabu\Cms\Widgets\TermsWidget;
use Javaabu\DynamicMenubuilder\DynamicMenubuilder;
use Javaabu\DynamicMenubuilder\Facades\Menus;
use Javaabu\DynamicMenubuilder\Support\MenuTree;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\Support\Translations as PostTranslations;
use Javaabu\Translatable\Translatable;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Support\Translations as WidgetTranslations;

/**
 * Content for a fresh site, in English and Dhivehi: a General category, an
 * About page, a welcome news article, a main and a footer menu, and sidebar
 * widgets. Each piece is only created when it isn't there yet, so running it
 * again is safe.
 */
class StarterContent
{
    /** The About page's title, also its menu items' label */
    protected const array ABOUT = ['en' => 'About us', 'dv' => 'އަޅުގަނޑުމެންނާ ބެހޭ'];

    /** @var array<int, string> */
    protected array $created = [];

    /**
     * Create what's missing
     *
     * @return array<int, string> what was created, for the console
     */
    public function seed(): array
    {
        $this->created = [];

        $general = $this->category();
        $about = $this->aboutPage();
        $this->welcomePost($general);
        $this->menus($about);
        $this->widgets();

        return $this->created;
    }

    protected function category(): ?Term
    {
        if (! PostTypes::findTaxonomy('categories')) {
            return null;
        }

        $model = PostTypes::termModel();

        $existing = $model::query()->where('taxonomy', 'categories')->where('slug', 'general')->first();

        if ($existing) {
            return $existing;
        }

        $term = new $model;
        $term->fill([
            'taxonomy' => 'categories',
            'slug' => 'general',
            ...$this->text($term, 'name', ['en' => 'General', 'dv' => 'އާންމު']),
        ]);
        $term->save();

        $this->created[] = __('Category: :name', ['name' => 'General']);

        return $term;
    }

    protected function aboutPage(): ?Post
    {
        $type = PostTypes::find('page');

        if (! $type) {
            return null;
        }

        $existing = $type->query()->where('slug', 'about')->first();

        if ($existing) {
            return $existing;
        }

        $post = $type->newPost();
        $post->fill([
            'slug' => 'about',
            'status' => 'published',
            'published_at' => Carbon::now(),
            ...$this->text($post, 'title', self::ABOUT),
            ...$this->text($post, 'content', [
                'en' => $this->document('Tell visitors who you are and what you do. Edit this page from the admin.'),
                'dv' => $this->document('ތިޔަ ފަރާތަކީ ކާކުކަމާއި ކުރާ މަސައްކަތުގެ ވާހަކަ މިތާ ލިޔުއްވާ. މި ސަފްހާ އެޑްމިނުން ބަދަލުކުރެވޭނެ.'),
            ]),
        ]);
        $post->save();

        $this->created[] = __('Page: :title', ['title' => 'About us']);

        return $post;
    }

    protected function welcomePost(?Term $category): ?Post
    {
        $type = PostTypes::find('news');

        if (! $type || $type->query()->exists()) {
            return null;
        }

        $post = $type->newPost();
        $post->fill([
            'slug' => 'welcome',
            'status' => 'published',
            'published_at' => Carbon::now(),
            ...$this->text($post, 'title', ['en' => 'Welcome to your new site', 'dv' => 'އާ ވެބްސައިޓަށް މަރުޙަބާ']),
            ...$this->text($post, 'excerpt', [
                'en' => 'Your site is ready. Write your first news article from the admin.',
                'dv' => 'ވެބްސައިޓު ތައްޔާރު. ފުރަތަމަ ޚަބަރު އެޑްމިނުން ލިޔުއްވާ.',
            ]),
            ...$this->text($post, 'content', [
                'en' => $this->document('Your site is ready. Write your first news article from the admin, and delete this one.'),
                'dv' => $this->document('ވެބްސައިޓު ތައްޔާރު. ފުރަތަމަ ޚަބަރު އެޑްމިނުން ލިޔުއްވާ، އަދި މި ޚަބަރު ފޮހެލައްވާ.'),
            ]),
        ]);

        if ($category && $type->hasTaxonomy('categories')) {
            $post->taxonomies = ['categories' => [$category->getKey()]];
        }

        $post->save();

        $this->created[] = __('News article: :title', ['title' => 'Welcome to your new site']);

        return $post;
    }

    protected function menus(?Post $about): void
    {
        if (Menus::hasLocation('header') && ! Menus::menuFor('header')) {
            $items = [
                ['key' => 'home', 'type' => 'link', ...$this->menuText(['en' => 'Home', 'dv' => 'ފުރަތަމަ ސަފްހާ'], url: '/')],
            ];

            if (Menus::hasLinkable('news')) {
                $items[] = ['key' => 'news', 'type' => 'index', 'source' => 'news', ...$this->menuText(['en' => 'News', 'dv' => 'ޚަބަރު'])];
            }

            if ($about && Menus::hasLinkable('page')) {
                $items[] = ['key' => 'about', 'type' => 'model', 'source' => 'page', 'linkable_id' => (string) $about->getKey(), ...$this->modelItemText(self::ABOUT)];
            }

            $this->menu('Main menu', 'header', $items);
        }

        if (Menus::hasLocation('footer') && ! Menus::menuFor('footer') && $about && Menus::hasLinkable('page')) {
            $this->menu('Footer menu', 'footer', [
                ['key' => 'about', 'type' => 'model', 'source' => 'page', 'linkable_id' => (string) $about->getKey(), ...$this->modelItemText(self::ABOUT)],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function menu(string $name, string $location, array $items): void
    {
        $model = DynamicMenubuilder::menuModel();

        $menu = $model::query()->where('name', $name)->first() ?? $model::create(['name' => $name]);

        if (! $menu->items()->exists()) {
            MenuTree::sync($menu, $items);
        }

        $menu->assignLocations([...$menu->locationKeys(), $location]);

        $this->created[] = __('Menu: :name', ['name' => $name]);
    }

    protected function widgets(): void
    {
        if (! Widgets::hasLocation('sidebar') || Widgets::widgets('sidebar')->isNotEmpty()) {
            return;
        }

        $model = Widgets::model();
        $order = 0;

        if (Widgets::exists(RecentPostsWidget::key()) && PostTypes::find('news')) {
            $model::create([
                'location' => 'sidebar',
                'type' => RecentPostsWidget::key(),
                'order' => $order++,
                'settings' => [
                    'title' => $this->widgetText(['en' => 'Latest news', 'dv' => 'އެންމެ ފަހުގެ ޚަބަރު']),
                    'post_type' => 'news',
                    'count' => 5,
                    'show_date' => true,
                ],
            ]);
        }

        if (Widgets::exists(TermsWidget::key()) && PostTypes::find('news')?->hasTaxonomy('categories')) {
            $model::create([
                'location' => 'sidebar',
                'type' => TermsWidget::key(),
                'order' => $order++,
                'settings' => [
                    'title' => $this->widgetText(['en' => 'Categories', 'dv' => 'ކެޓެގަރީތައް']),
                    'source' => 'news:categories',
                    'show_counts' => false,
                    'hide_empty' => true,
                ],
            ]);
        }

        if ($order > 0) {
            Widgets::flush();

            $this->created[] = __('Sidebar widgets');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    */

    /**
     * A translatable field's values: field_en and field_dv for the active
     * languages when the model is translatable, else the field in the app's
     * language
     *
     * @param  array<string, mixed>  $values  by locale
     * @return array<string, mixed>
     */
    protected function text(Model $model, string $field, array $values): array
    {
        if (PostTranslations::supports($model, $field)) {
            $attributes = [];

            foreach ($this->locales() as $locale) {
                $attributes["{$field}_{$locale}"] = $values[$locale] ?? $values['en'];
            }

            return $attributes;
        }

        return [$field => $this->inAppLocale($values)];
    }

    /**
     * A menu item's title (and URL), for MenuTree::sync()
     *
     * @param  array<string, string>  $titles
     * @return array<string, string|null>
     */
    protected function menuText(array $titles, ?string $url = null): array
    {
        $data = ['title' => $this->inAppLocale($titles), 'url' => $url];

        foreach ($this->locales() as $locale) {
            $data["title_{$locale}"] = $titles[$locale] ?? $titles['en'];
            $data["url_{$locale}"] = $url;
        }

        return $data;
    }

    /**
     * A label per language for a menu item that links to a record. Without
     * translations the item has none and follows the record's title.
     *
     * @param  array<string, string>  $titles
     * @return array<string, string|null>
     */
    protected function modelItemText(array $titles): array
    {
        return $this->locales() === [] ? [] : $this->menuText($titles);
    }

    /**
     * A widget setting: a value per language when widgets are translatable
     *
     * @param  array<string, string>  $values
     * @return array<string, string>|string
     */
    protected function widgetText(array $values): array|string
    {
        if (! WidgetTranslations::enabled()) {
            return $this->inAppLocale($values);
        }

        $translated = [];

        foreach ($this->locales() as $locale) {
            $translated[$locale] = $values[$locale] ?? $values['en'];
        }

        return $translated;
    }

    /**
     * @return array<int, string>
     */
    protected function locales(): array
    {
        if (! Translations::enabled()) {
            return [];
        }

        return Translatable::locales()->map(fn ($locale) => $locale->code)->values()->all();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function inAppLocale(array $values): mixed
    {
        return $values[app()->getLocale()] ?? $values['en'];
    }

    /**
     * A block-editor document with one paragraph
     *
     * @return array<string, mixed>
     */
    protected function document(string $text): array
    {
        return [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]],
            ],
        ];
    }
}
