<?php

namespace Javaabu\Cms\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\Cms\Facades\Cms;
use Javaabu\Cms\Views\ViewRenderer;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Http\PublicData;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Routing\PostTypeRoutes;

/**
 * The home page: the front page chosen in the site settings (if any), then
 * the latest posts of the home post type. Extend it and set
 * config('cms.controllers.home') to change it (override data()).
 *
 * Blade: `cms.home` in the app's views, else `cms::public.home`.
 * Inertia: `cms::Public/Home`.
 */
class HomeController extends Controller
{
    public function __construct(
        protected ViewRenderer $views,
    ) {}

    public function index(Request $request): View|Responsable|JsonResponse
    {
        $data = $this->data($request);

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $data]);
        }

        return $this->views->render(['cms.home', 'cms::public.home'], 'cms::Public/Home', [
            ...$data,
            'site' => Cms::site(),
            'strings' => $this->strings($data['post_type'] ? PostTypes::find($data['post_type']['key']) : null),
            'layout' => Cms::layout('public'),
            'layout_section' => Cms::layoutSection(),
        ]);
    }

    /**
     * @return array{page: array<string, mixed>|null, post_type: array<string, mixed>|null, posts: array<int, array<string, mixed>>, archive_url: string|null}
     */
    protected function data(Request $request): array
    {
        $page = Cms::frontPage();
        $type = Cms::homePostType();

        return [
            'page' => $page ? $this->page($page) : null,
            'post_type' => $type?->toArray(),
            'posts' => $type ? $this->posts($type) : [],
            'archive_url' => $type ? PostTypeRoutes::archiveUrl($type) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function page(Post $page): array
    {
        $type = $page->postType();

        return $type ? PublicData::make($type)->full($page) : ['id' => $page->getKey(), 'title' => (string) $page->title];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function posts(PostType $type): array
    {
        $query = $type->query()->published()->latestPublished()->with(['terms', 'author']);

        if ($type->supportsFeature(PostType::MEDIA)) {
            $query->withAttachments();
        }

        $data = PublicData::make($type);

        return $query->limit(max(1, (int) config('cms.home.count', 6)))
            ->get()
            ->map(fn (Post $post) => $data->card($post))
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function strings(?PostType $type): array
    {
        return [
            ...($type ? PublicData::make($type)->strings() : []),
            'latest' => $type ? __('Latest :type', ['type' => mb_strtolower($type->getPluralLabel())]) : __('Latest posts'),
            'view_all' => __('View all'),
            'welcome' => __('Welcome'),
            'empty' => __('Nothing has been published here yet.'),
        ];
    }
}
