<?php

namespace Javaabu\Cms\Navigation;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * A link in the admin navigation
 *
 *     NavigationItem::make('reports')
 *         ->label(fn () => __('Reports'))
 *         ->route('reports.index')        // relative to the admin route names: admin.reports.index
 *         ->icon('chart-column')
 *         ->group('content')
 *         ->can('viewAny', Report::class);
 */
class NavigationItem
{
    protected Closure|string|null $label = null;

    protected ?string $route = null;

    /** @var array<string, mixed> */
    protected array $parameters = [];

    protected bool $relative = true;

    protected Closure|string|null $url = null;

    protected ?string $icon = null;

    protected ?string $group = null;

    protected int $order = 100;

    protected Closure|string|null $ability = null;

    protected mixed $arguments = [];

    /** @var array<int, string> */
    protected array $active = [];

    /** @var array<int, NavigationItem> */
    protected array $children = [];

    public function __construct(protected string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    /**
     * The text of the link: a string (translated when shown) or a closure
     */
    public function label(Closure|string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Link to a named route. Relative names get the admin route name prefix
     * (`admin.`) in front; pass relative: false for a full name.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function route(string $name, array $parameters = [], bool $relative = true): static
    {
        $this->route = $name;
        $this->parameters = $parameters;
        $this->relative = $relative;

        return $this;
    }

    /**
     * Link to a URL instead of a route
     */
    public function url(Closure|string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * A Lucide icon name (newspaper, file-text, …)
     */
    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * The key of the group the link is in (Navigation::group())
     */
    public function group(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function order(int $order): static
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Only show the link to users with this ability (a gate ability or a
     * permission name, with its arguments), or for whom the closure returns
     * true: fn (?Model $user) => …
     */
    public function can(Closure|string $ability, mixed $arguments = []): static
    {
        $this->ability = $ability;
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * Route name patterns (relative like route()) that mark the link as the
     * current page. Defaults to the route's resource: `news.*` for `news.index`.
     *
     * @param  array<int, string>|string  $patterns
     */
    public function active(array|string $patterns): static
    {
        $this->active = (array) $patterns;

        return $this;
    }

    /**
     * Links shown under this one
     *
     * @param  array<int, NavigationItem>  $children
     */
    public function children(array $children): static
    {
        $this->children = array_values($children);

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        $label = $this->label instanceof Closure ? ($this->label)() : $this->label;

        return $label === null ? Str::headline($this->key) : __($label);
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return array<int, NavigationItem>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * The link's URL, or null when its route isn't registered
     */
    public function resolveUrl(string $prefix = ''): ?string
    {
        if ($this->url !== null) {
            return $this->url instanceof Closure ? ($this->url)() : $this->url;
        }

        if ($this->route === null) {
            return null;
        }

        $name = $this->routeName($prefix);

        return Route::has($name) ? route($name, $this->parameters) : null;
    }

    public function allows(?Model $user): bool
    {
        if ($this->ability === null) {
            return true;
        }

        if ($this->ability instanceof Closure) {
            return (bool) ($this->ability)($user);
        }

        if (! $user) {
            return false;
        }

        $arguments = $this->arguments instanceof Closure ? ($this->arguments)() : $this->arguments;

        return Gate::forUser($user)->allows($this->ability, $arguments);
    }

    public function isActive(?string $current, string $prefix = ''): bool
    {
        if ($current === null) {
            return false;
        }

        foreach ($this->activePatterns($prefix) as $pattern) {
            if (Str::is($pattern, $current)) {
                return true;
            }
        }

        foreach ($this->children as $child) {
            if ($child->isActive($current, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The link for this user, or null when it's hidden from them (no
     * ability, no route and no visible children)
     *
     * @return array{key: string, label: string, url: string|null, icon: string|null, active: bool, children: array<int, array<string, mixed>>}|null
     */
    public function resolve(?Model $user, string $prefix = '', ?string $current = null): ?array
    {
        if (! $this->allows($user)) {
            return null;
        }

        $children = Navigation::sortItems($this->children);
        $children = array_values(array_filter(array_map(
            fn (NavigationItem $child) => $child->resolve($user, $prefix, $current),
            $children,
        )));

        $url = $this->resolveUrl($prefix);

        if ($url === null && $children === []) {
            return null;
        }

        return [
            'key' => $this->key,
            'label' => $this->getLabel(),
            'url' => $url,
            'icon' => $this->icon,
            'active' => $this->isActive($current, $prefix),
            'children' => $children,
        ];
    }

    protected function routeName(string $prefix): string
    {
        return $this->relative ? $prefix . $this->route : (string) $this->route;
    }

    /**
     * @return array<int, string>
     */
    protected function activePatterns(string $prefix): array
    {
        if ($this->active !== []) {
            return array_map(fn (string $pattern) => $this->relative ? $prefix . $pattern : $pattern, $this->active);
        }

        if ($this->route === null) {
            return [];
        }

        // news.index → news.*, but dashboard → dashboard (not every admin route)
        $route = $this->route;
        $pattern = Str::contains($route, '.') ? Str::beforeLast($route, '.') . '.*' : $route;

        return [$this->relative ? $prefix . $pattern : $pattern];
    }
}
