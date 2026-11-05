<?php

namespace Javaabu\Cms\Settings;

use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\Settings\Definitions\Section;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;

/**
 * The "Site" settings page (javaabu/settings): the site's name and tagline,
 * what the home page shows, and the footer text. Read them with
 * Cms::siteName(), Cms::tagline(), … or settings('cms_site_name').
 */
class SiteSettings extends SettingsPage
{
    protected ?string $key = 'site';

    protected ?string $permission = 'edit_site_settings';

    protected int $order = -10;

    public function getLabel(): string
    {
        return __('Site');
    }

    public function getDescription(): ?string
    {
        return __('The site\'s name, its home page and its footer.');
    }

    public function getIcon(): ?string
    {
        return 'globe';
    }

    public function definition(): array
    {
        return [
            Setting::make('cms_site_name')
                ->default(fn () => config('app.name'))
                ->label(__('Site name'))
                ->required()
                ->maxLength(255)
                ->translatable(),
            Setting::make('cms_tagline')
                ->label(__('Tagline'))
                ->help(__('A few words under the site name.'))
                ->maxLength(255)
                ->translatable(),
            Section::make(__('Home page'))->schema([
                Setting::make('cms_front_page')
                    ->field(Select::class)
                    ->label(__('Page shown on the home page'))
                    ->help(__('Leave empty to show the latest posts.'))
                    ->placeholder(__('Latest posts'))
                    ->options(fn () => static::pageOptions())
                    ->cast('int'),
                Setting::make('cms_home_post_type')
                    ->field(Select::class)
                    ->default(fn () => config('cms.home.post_type', 'news'))
                    ->label(__('Latest posts from'))
                    ->options(fn () => static::postTypeOptions()),
            ]),
            Section::make(__('Footer'))->schema([
                Setting::make('cms_footer_text')
                    ->field(Textarea::class)
                    ->label(__('Footer text'))
                    ->maxLength(1000)
                    ->translatable(),
            ]),
        ];
    }

    /**
     * Published pages, by title
     *
     * @return array<int|string, string>
     */
    public static function pageOptions(): array
    {
        $type = PostTypes::find('page');

        if (! $type) {
            return [];
        }

        return $type->query()
            ->published()
            ->orderBy('title')
            ->limit(500)
            ->get()
            ->mapWithKeys(fn ($post) => [$post->getKey() => (string) $post->title])
            ->all();
    }

    /**
     * Public post types with an archive
     *
     * @return array<string, string>
     */
    public static function postTypeOptions(): array
    {
        $options = [];

        foreach (PostTypes::types() as $key => $type) {
            if ($type->isPublic() && $type->hasArchive()) {
                $options[$key] = $type->getPluralLabel();
            }
        }

        return $options;
    }
}
