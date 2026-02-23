<?php
/**
 * Methods that all root slug models should have
 */

namespace Javaabu\Cms\Enums\RootSlugs;

trait HasRootSlug
{
    /**
     * Boot function from laravel.
     */
    public static function bootHasRootSlug()
    {
        static::saved(function () {
            app(RootSlugsRegistrar::class)->forgetCachedRootSlugs();
        });

        static::deleted(function () {
            app(RootSlugsRegistrar::class)->forgetCachedRootSlugs();
        });
    }
}
