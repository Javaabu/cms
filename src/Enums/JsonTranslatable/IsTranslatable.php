<?php
/**
 * Methods that all translatable models should have
 */

namespace Javaabu\Cms\Enums\JsonTranslatable;


trait IsTranslatable
{
    /**
     * Fields to ignore for translations
     *
     * @var array
     */
    protected $ignore_for_translation = [
        'updated_at',
        'created_at',
        'deleted_at',
        'id',
        'translations',
    ];

    /**
     * Get the translation ignored fields
     *
     * @return array
     */
    public function getFieldsIgnoredForTranslation()
    {
        return $this->ignore_for_translation;
    }

    /**
     * Check is default translation locale
     *
     * @param string $locale
     * @return boolean
     */
    public function isDefaultTranslationLocale($locale)
    {
        return strtolower($this->getDefaultTranslationLocale()) == strtolower($locale);
    }

    /**
     * Get default translation locale
     *
     * @return string
     */
    public function getDefaultTranslationLocale()
    {
        return Languages::getDefaultTranslationLocale();
    }

    /**
     * Get allowed translation locales
     *
     * @return array
     */
    public function getAllowedTranslationLocales()
    {
        return Languages::getKeys();
    }

    /**
     * Check if given locale is allowed
     *
     * @param string $locale
     * @return boolean
     */
    public function isAllowedTranslationLocale($locale)
    {
        return true;
    }

    /**
     * Get all pivots and attributes that must not be translatable
     *
     * @return array
     */
    public function getAllNonTranslatables()
    {
        return array_merge(
            $this->getNonTranslatableAttachmentCollections(),
            $this->getNonTranslatablePivots(),
            $this->getNonTranslatables()
        );
    }

    /**
     * Get all attachment collection that must not be translatable
     *
     * @return array
     */
    public function getNonTranslatableAttachmentCollections()
    {
        return property_exists($this, 'non_translatable_attachment_collections') ? $this->non_translatable_attachment_collections : [];
    }

    /**
     * Get all pivots that must not be translatable
     *
     * @return array
     */
    public function getNonTranslatablePivots()
    {
        return property_exists($this, 'non_translatable_pivots') ? $this->non_translatable_pivots : [];
    }

    /**
     * Check if is a non translatable pivot
     *
     * @param string $relation
     * @return boolean
     */
    public function isNonTranslatablePivot($relation)
    {
        return in_array($relation, $this->getNonTranslatablePivots());
    }

    /**
     * Check if is a non translatable attachment collection
     *
     * @param string $collection
     * @return boolean
     */
    public function isNonTranslatableAttachmentCollection($collection)
    {
        return in_array($collection, $this->getNonTranslatableAttachmentCollections());
    }
}
