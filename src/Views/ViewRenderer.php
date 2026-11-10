<?php

namespace Javaabu\Cms\Views;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;
use Inertia\Inertia;
use RuntimeException;

/**
 * Renders a page with the stack chosen in `config('cms.views')`
 */
class ViewRenderer
{
    public const string BLADE = 'blade';

    public const string INERTIA = 'inertia';

    /**
     * @param  string|array<int, string>  $view  Blade views, the first that exists wins
     * @param  string  $page  the Inertia page, e.g. `cms::Admin/Dashboard`
     * @param  array<string, mixed>  $data
     */
    public function render(string|array $view, string $page, array $data = []): View|Responsable
    {
        if ($this->isInertia()) {
            if (! class_exists(Inertia::class)) {
                throw new RuntimeException('Install inertiajs/inertia-laravel to use the inertia views, or set cms.views to blade.');
            }

            return Inertia::render($page, $data);
        }

        return ViewFactory::first((array) $view, $data);
    }

    public function stack(): string
    {
        return config('cms.views', self::BLADE) === self::INERTIA ? self::INERTIA : self::BLADE;
    }

    public function isInertia(): bool
    {
        return $this->stack() === self::INERTIA;
    }
}
