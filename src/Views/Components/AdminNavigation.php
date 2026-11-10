<?php

namespace Javaabu\Cms\Views\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\Component;
use Javaabu\Cms\Facades\Cms;
use Javaabu\Cms\Routing\CmsRoutes;

/**
 * <x-cms::admin-navigation /> — the admin navigation for the current user
 */
class AdminNavigation extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $groups;

    public function __construct(Request $request, ?string $guard = null)
    {
        $user = auth()->guard($guard ?? CmsRoutes::guard($request))->user();

        $this->groups = Cms::navigationFor($user instanceof Model ? $user : null, $request);
    }

    public function render(): View
    {
        return view('cms::components.admin-navigation');
    }
}
