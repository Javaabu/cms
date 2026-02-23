<?php
/**
 * Methods that all types of translatable models should have
 */

namespace Javaabu\Cms\Enums\JsonTranslatable;


interface Translatable
{
    /**
     * Get the translation ignored fields
     *
     * @return array
     */
    public function getFieldsIgnoredForTranslation();

    /**
     * Get the translatable fields
     *
     * @return array
     */
    public function getTranslatables();

    /**
     * Get all attributes that must not be translatable
     *
     * @return array
     */
    public function getNonTranslatables();

    /**
     * Get all pivots that must not be translatable
     *
     * @return array
     */
    public function getNonTranslatablePivots();

    /**
     * Get all attachment collection that must not be translatable
     *
     * @return array
     */
    public function getNonTranslatableAttachmentCollections();

    /**
     * Get all pivots and attributes that must not be translatable
     *
     * @return array
     */
    public function getAllNonTranslatables();

    /**
     * Check if is a non translatable pivot
     *
     * @param string $relation
     * @return boolean
     */
    public function isNonTranslatablePivot($relation);

    /**
     * Check if is a non translatable attachment collection
     *
     * @param string $collection
     * @return boolean
     */
    public function isNonTranslatableAttachmentCollection($collection);

    /**
     * Translate the given field to given locale.
     * Fall back to default if no translation
     *
     * @param $field
     * @param null $local
     * @param bool $fallback
     * @return string
     */
    public function translate($field, $locale);

    /**
     * Check whether the given field is translatable
     *
     * @param string $field
     * @return boolean
     */
    public function isTranslatable($field);

    /**
     * Clear the translations for the given locale or all
     *
     * @param null $locale
     */
    public function clearTranslations();

    /**
     * Get the admin localized url
     *
     * @param null $locale
     * @return string
     */
    public function getAdminLocalizedUrl();

    /**
     * Get the localized url
     *
     * @param null $locale
     * @return string
     */
    public function getLocalizedUrl($locale = null);

    /**
     * Returns the url
     *
     * @param string $action
     * @param string|null $locale
     * @param string $namespace
     * @return string
     */
    public function url(string $action = 'show', string $locale = null, string $namespace = 'admin'): string;

    /**
     * Check if has any translations or translations for a
     * specific locale
     *
     * @param null $locale
     * @return bool
     */
    public function hasTranslations();

    /**
     * Check is default translation locale
     *
     * @param string $locale
     * @return boolean
     */
    public function isDefaultTranslationLocale($locale);

    /**
     * Get default translation locale
     *
     * @return string
     */
    public function getDefaultTranslationLocale();

    /**
     * Get allowed translation locales
     *
     * @return array
     */
    public function getAllowedTranslationLocales();

    /**
     * Check if given locale is allowed
     *
     * @param string $locale
     * @return boolean
     */
    public function isAllowedTranslationLocale($locale);
}
