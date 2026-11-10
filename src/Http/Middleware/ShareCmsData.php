<?php

namespace Javaabu\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Javaabu\Cms\Facades\Cms;
use Javaabu\Cms\Routing\CmsRoutes;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares what the React layouts need with every Inertia page of a CMS route:
 * `cms.site` everywhere, `cms.navigation` on admin pages, and `menus` and
 * `widgets` on public pages. Lazy, so nothing is queried for Blade pages or
 * partial reloads that don't ask for it. Does nothing without Inertia.
 */
class ShareCmsData
{
    public function handle(Request $request, Closure $next, string $area = 'public'): Response
    {
        if (class_exists(Inertia::class)) {
            Inertia::share('cms', fn () => [
                'area' => $area,
                'site' => Cms::site(),
                'strings' => Cms::strings(),
                'navigation' => $area === 'admin'
                    ? Cms::navigationFor($request->user(CmsRoutes::guard($request)), $request)
                    : [],
            ]);

            if ($area === 'public') {
                Inertia::share(config('dynamic-menubuilder.share_key', 'menus'), fn () => Cms::menus());
                Inertia::share('widgets', fn () => Cms::widgets());
            }
        }

        return $next($request);
    }
}
