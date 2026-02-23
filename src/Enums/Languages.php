<?php

namespace Javaabu\Cms\Enums;

use Javaabu\Cms\Enums\JsonTranslatable\Translatable;
use Javaabu\Helpers\Enums\IsEnum;
use Illuminate\Support\Facades\Route;
use Javaabu\Helpers\Traits\EnumsTrait;
use Javaabu\Helpers\Enums\NativeEnumsTrait;

enum Languages: string implements IsEnum
{
    use NativeEnumsTrait;

    case EN = 'en';
    case DV = 'dv';
    /**
     * Flags
     */
    protected static function flags() {
        return [
            self::EN->value => 'gb',
            self::DV->value => 'mv',
        ];
    }

    public static function getDefaultAppLocale()
    {
        return config('app.fallback_locale');
    }

    public static function getOppositeLocaleFlag($current_locale = null): string
    {
        return self::getLocaleFlag($current_locale, true);
    }

    public static function getLocaleFlag($current_locale = null, $opposite = false): string
    {
        if (! $current_locale) {
            $current_locale = app()->getLocale();
        }

        $locale = $opposite ? self::getOppositeLocale() : $current_locale;

        return self::flagUrl($locale);
    }

    public static function getOppositeLocale($currentLocale = null): string
    {
        if (! $currentLocale) {
            $currentLocale = app()->getLocale();
        }

        return $currentLocale == self::DV->value ? self::EN->value : self::DV->value;
    }

    public static function flagUrl($key): string
    {
        return Flags::getFlagUrl(self::getFlag($key));
    }

    protected static function getFlag($key): string
    {
        return static::flags()[$key] ?? '';
    }

    public static function getDirection($current_locale = null): string
    {
        if (! $current_locale) {
            $current_locale = app()->getLocale();
        }

        return self::isRtl($current_locale) ? 'rtl' : 'ltr';
    }

    public static function isRtl($value): bool
    {
        return $value == self::DV->value;
    }

    public static function translateCurrentRoute(): string
    {
        $current_route = Route::getCurrentRoute()->getName();

        $route_params = Route::getCurrentRoute()->parameters();

        $switch_to = self::getOppositeLocale();

        $route_params['language'] = $switch_to;

        return route($current_route, $route_params);
    }

    public static function getAdminLocalizedUrl($translatable, $locale = null): string
    {
        if (! $locale) {
            $locale = self::getOppositeLocale();
        }

        $url = null;

        if ($translatable instanceof Translatable) {
            $url = $translatable->getLocalizedUrl($locale);
        } elseif ($translatable) {
            $url = admin_url('/' . $locale . '/' . ltrim($translatable, '/'));
        }

        return $url ?: admin_url('/' . $locale);
    }

    public static function getLocalizedUrl($translatable, $locale = null): string
    {
        if (! $locale) {
            $locale = self::getOppositeLocale();
        }

        $url = null;

        if ($translatable instanceof Translatable) {
            $url = $translatable->getLocalizedUrl($locale);
        } elseif ($translatable) {
            $url = public_url('/' . $locale . '/' . ltrim($translatable, '/'));
        }

        return $url ?: public_url('/' . $locale);
    }

    /**
     * Set current session locale
     *
     * @return string
     */
    public static function getSessionLocale(): string
    {
        return session()->get('language', static::getDefaultTranslationLocale());
    }

    public static function getDefaultTranslationLocale(): string
    {
        return config('translations.default_translation_locale');
    }

    /**
     * Initialize labels
     */
    public static function getLabels(): array
    {
        return [
            self::DV->value => __('Dhivehi'),
            self::EN->value => __('English'),
        ];
    }

    /**
     * Check if is a valid key
     *
     * @param $key
     * @return bool
     */
    public static function isValidKey($key): bool
    {
        return array_key_exists($key, self::getLabels());
    }
}
