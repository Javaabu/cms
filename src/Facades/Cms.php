<?php

namespace Javaabu\Cms\Facades;

use Illuminate\Support\Facades\Facade;
use Javaabu\Cms\CmsManager;

/**
 * @method static \Javaabu\Cms\Navigation\Navigation navigation()
 * @method static array navigationFor(?\Illuminate\Database\Eloquent\Model $user, ?\Illuminate\Http\Request $request = null)
 * @method static string siteName()
 * @method static string|null tagline()
 * @method static string|null footerText()
 * @method static \Javaabu\PostTypes\Models\Post|null frontPage()
 * @method static \Javaabu\PostTypes\PostType|null homePostType()
 * @method static array site()
 * @method static array menus()
 * @method static array widgets()
 * @method static string views()
 * @method static string layout(string $area = 'public')
 * @method static string layoutSection()
 *
 * @see CmsManager
 */
class Cms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CmsManager::class;
    }
}
