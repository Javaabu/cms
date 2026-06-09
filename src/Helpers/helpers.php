<?php

use Javaabu\Cms\Enums\Languages;

if (! function_exists('translate_route')) {
    /**
     * Generate a translatable route for the application.
     *
     * @param string $name
     * @param array|string|mixed $parameters
     * @param bool $absolute
     * @param null $locale
     * @return string
     */
    function translate_route(string $name, $parameters = [], bool $absolute = true, $locale = null): string
    {
        if (! config('cms.should_translate')) {
            return route($name, $parameters, $absolute);
        }

        if (! $locale) {
            $locale = app()->getLocale();
        }

        $parameters = Arr::wrap($parameters);
        $parameters['language'] = $locale;

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('translate_action')) {
    /**
     * Generate the URL to a controller action.
     *
     * @param string $name
     * @param array|string|mixed $parameters
     * @param bool $absolute
     * @param null $locale
     * @return string
     */
    function translate_action(string $name, $parameters = [], bool $absolute = true, $locale = null): string
    {
        if (! config('cms.should_translate')) {
            return action($name, $parameters, $absolute);
        }

        if (! $locale) {
            $locale = app()->getLocale();
        }

        if (is_array($parameters)) {
            $parameters = array_merge(['language' => $locale], $parameters);

            return action($name, $parameters, $absolute);
        }

        if ($parameters) {
            // Scalar parameters are treated as query values to avoid ambiguous
            // route matching between translated and untranslated controller actions.
            return add_query_arg(
                'language',
                $locale,
                add_query_arg(Arr::wrap($parameters), action($name, [], $absolute))
            );
        }

        return action($name, ['language' => $locale], $absolute);
    }
}

if (! function_exists('admin_url')) {
    /**
     * Get a public url
     *
     * @param string $path
     * @param null $locale
     * @return string
     */
    function admin_url(string $path = '/', $locale = null)
    {
        return portal_url($path, 'admin', $locale);
    }
}

if (! function_exists('_d')) {
    /**
     * Translate the given message and use the default locale if locale not specified.
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string|array|null
     */
    function _d(string $key, array $replace = [], string $locale = null)
    {
        $locale = $locale ?: Languages::getDefaultAppLocale();
        return __($key, $replace, $locale);
    }
}

if (! function_exists('add_tab_class')) {
    /**
     * Adds tab active class if tab is active
     *
     * @param bool $active
     * @param string $classes
     * @param string $active_class
     * @return string
     */
    function add_tab_class(bool $active, string $classes = 'tab-pane fade', string $active_class = 'active show'): string
    {
        return $active ? $classes . ' ' . $active_class : $classes;
    }
}
