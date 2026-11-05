<?php

namespace Javaabu\Cms\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Javaabu\Cms\Dashboard\Dashboard;
use Javaabu\Cms\Facades\Cms;
use Javaabu\Cms\Routing\CmsRoutes;
use Javaabu\Cms\Views\ViewRenderer;

/**
 * The admin dashboard: counts per post type and the posts edited most
 * recently. Extend it and set config('cms.controllers.dashboard') to add
 * to it (override data()).
 */
class DashboardController extends Controller
{
    public function __construct(
        protected ViewRenderer $views,
    ) {}

    public function index(Request $request): View|Responsable|JsonResponse
    {
        $user = $this->user($request);

        Gate::forUser($user)->authorize('view', Dashboard::class);

        $data = $this->data($request, $user);

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $data]);
        }

        return $this->views->render('cms::admin.dashboard', 'cms::Admin/Dashboard', [
            ...$data,
            'strings' => $this->strings(),
            'layout' => Cms::layout('admin'),
            'layout_section' => Cms::layoutSection(),
        ]);
    }

    /**
     * What the dashboard shows: post_types (counts and links) and recent posts
     *
     * @return array<string, mixed>
     */
    protected function data(Request $request, ?Model $user): array
    {
        return Dashboard::for($user, CmsRoutes::adminName($request))->toArray();
    }

    /**
     * @return array<string, string>
     */
    protected function strings(): array
    {
        return [
            'title' => __('Dashboard'),
            'content' => __('Content'),
            'recent' => __('Recently edited'),
            'published' => __('Published'),
            'scheduled' => __('Scheduled'),
            'draft' => __('Drafts'),
            'pending' => __('Pending review'),
            'add' => __('Add :type', ['type' => ':type']),
            'view_all' => __('View all'),
            'edit' => __('Edit'),
            'empty' => __('Nothing has been written yet.'),
            'no_types' => __('You can\'t see any content yet.'),
            'updated' => __('Updated :date', ['date' => ':date']),
        ];
    }

    protected function user(Request $request): ?Model
    {
        $user = $request->user(CmsRoutes::guard($request));

        return $user instanceof Model ? $user : null;
    }
}
