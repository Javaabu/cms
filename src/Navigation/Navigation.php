<?php

namespace Javaabu\Cms\Navigation;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * The admin navigation: groups of links, filtered for each user by ability
 * and by the routes that are registered. Apps add their own links:
 *
 *     Cms::navigation()->add(NavigationItem::make('reports')->route('reports.index')->group('content'));
 *
 * Links that depend on things registered later (every post type) come from
 * providers, called each time the navigation is resolved.
 */
class Navigation
{
    /** @var array<string, array{label: Closure|string, order: int}> */
    protected array $groups = [];

    /** @var array<string, NavigationItem> */
    protected array $items = [];

    /** @var array<int, Closure(): iterable<NavigationItem>> */
    protected array $providers = [];

    /** @var array<int, string> */
    protected array $removed = [];

    /**
     * Define a group (a heading with links under it)
     */
    public function group(string $key, Closure|string $label, int $order = 100): static
    {
        $this->groups[$key] = ['label' => $label, 'order' => $order];

        return $this;
    }

    /**
     * Add links. A link with the key of an existing one replaces it.
     */
    public function add(NavigationItem ...$items): static
    {
        foreach ($items as $item) {
            $this->items[$item->getKey()] = $item;
            $this->removed = array_values(array_diff($this->removed, [$item->getKey()]));
        }

        return $this;
    }

    /**
     * Links worked out when the navigation is resolved: fn () => [NavigationItem, …]
     *
     * @param  Closure(): iterable<NavigationItem>  $provider
     */
    public function provide(Closure $provider): static
    {
        $this->providers[] = $provider;

        return $this;
    }

    /**
     * Leave out links by key, including ones from providers (the CMS's own:
     * dashboard, post-type-news, taxonomy-categories, media, menus, widgets,
     * settings, roles)
     */
    public function remove(string ...$keys): static
    {
        foreach ($keys as $key) {
            unset($this->items[$key]);
            $this->removed[] = $key;
        }

        return $this;
    }

    public function get(string $key): ?NavigationItem
    {
        return $this->all()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Every link, before filtering, keyed by key
     *
     * @return array<string, NavigationItem>
     */
    public function all(): array
    {
        $items = [];

        foreach ($this->providers as $provider) {
            foreach ($provider() as $item) {
                $items[$item->getKey()] = $item;
            }
        }

        $items = [...$items, ...$this->items];

        foreach ($this->removed as $key) {
            unset($items[$key]);
        }

        return $items;
    }

    /**
     * The navigation for a user, as groups of links:
     * [['key' => 'content', 'label' => 'Content', 'items' => [...]], …]. Links
     * outside a group come first, in a group with a null label.
     *
     * @param  string  $prefix  the admin route name prefix, `admin.`
     * @param  string|null  $current  the current route name, to mark the active link
     * @return array<int, array{key: string, label: string|null, items: array<int, array<string, mixed>>}>
     */
    public function resolve(?Model $user, string $prefix = '', ?string $current = null): array
    {
        $grouped = [];

        foreach (static::sortItems(array_values($this->all())) as $item) {
            $resolved = $item->resolve($user, $prefix, $current);

            if ($resolved === null) {
                continue;
            }

            $group = $item->getGroup();
            $group = $group !== null && isset($this->groups[$group]) ? $group : '';

            $grouped[$group][] = $resolved;
        }

        $groups = [];

        foreach ($this->sortedGroupKeys() as $key) {
            if (empty($grouped[$key])) {
                continue;
            }

            $groups[] = [
                'key' => $key,
                'label' => $key === '' ? null : $this->groupLabel($key),
                'items' => $grouped[$key],
            ];
        }

        return $groups;
    }

    /**
     * @param  array<int, NavigationItem>  $items
     * @return array<int, NavigationItem>
     */
    public static function sortItems(array $items): array
    {
        $positions = array_flip(array_map(fn (NavigationItem $item) => $item->getKey(), $items));

        usort($items, fn (NavigationItem $a, NavigationItem $b) => [$a->getOrder(), $positions[$a->getKey()]] <=> [$b->getOrder(), $positions[$b->getKey()]]);

        return $items;
    }

    /**
     * @return array<int, string>
     */
    protected function sortedGroupKeys(): array
    {
        $groups = $this->groups;

        uasort($groups, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return ['', ...array_map('strval', array_keys($groups))];
    }

    protected function groupLabel(string $key): string
    {
        $label = $this->groups[$key]['label'];

        return $label instanceof Closure ? $label() : __($label);
    }
}
