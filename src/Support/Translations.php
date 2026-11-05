<?php

namespace Javaabu\Cms\Support;

use Javaabu\Translatable\Translatable;

/**
 * Optional javaabu/translatable support, in one place: every call checks the
 * package is installed first. The models themselves are translated by
 * post-types, widgets, dynamic-menubuilder and settings.
 */
class Translations
{
    /** Set to false to turn translations off even when the package is installed */
    public static bool $enabled = true;

    /**
     * Whether javaabu/translatable is installed (no database check: safe while routes register)
     */
    public static function installed(): bool
    {
        return static::$enabled
            && config('cms.translatable', true)
            && class_exists(Translatable::class);
    }

    /**
     * Whether javaabu/translatable is installed, enabled and has active languages
     */
    public static function enabled(): bool
    {
        return static::installed() && Translatable::enabled();
    }

    public static function currentLocale(): string
    {
        return static::enabled() ? Translatable::currentLocale() : app()->getLocale();
    }

    /**
     * The text direction of the current locale: rtl for Dhivehi
     */
    public static function direction(): string
    {
        if (static::enabled()) {
            return Translatable::locale()?->direction ?? 'ltr';
        }

        return in_array(app()->getLocale(), ['dv', 'ar', 'fa', 'he', 'ur'], true) ? 'rtl' : 'ltr';
    }

    /**
     * The active languages with a link to the current page in each, for a
     * language switcher. Empty without translations or with one language.
     *
     * @return array<int, array{code: string, name: string, native_name: string, direction: string, url: string, current: bool}>
     */
    public static function switcher(): array
    {
        if (! static::enabled()) {
            return [];
        }

        $current = Translatable::currentLocale();
        $locales = Translatable::locales();

        if ($locales->count() < 2) {
            return [];
        }

        return $locales->map(fn ($locale) => [
            'code' => $locale->code,
            'name' => $locale->name,
            'native_name' => $locale->native_name,
            'direction' => $locale->direction,
            'url' => Translatable::switchUrl($locale->code),
            'current' => $locale->code === $current,
        ])->values()->all();
    }
}
