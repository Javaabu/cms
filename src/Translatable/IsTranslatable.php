<?php

namespace Javaabu\Cms\Translatable;

use Javaabu\Cms\Enums\Languages;

trait IsTranslatable
{
    protected $ignore_for_translation = [
        'updated_at',
        'created_at',
        'deleted_at',
        'id',
        'translations',
    ];

    public function getFieldsIgnoredForTranslation()
    {
        return $this->ignore_for_translation;
    }

    public function isDefaultTranslationLocale($locale)
    {
        return strtolower($this->getDefaultTranslationLocale()) == strtolower($locale);
    }

    public function getDefaultTranslationLocale()
    {
        return Languages::getDefaultTranslationLocale();
    }

    public function getAllowedTranslationLocales()
    {
        return Languages::getKeys();
    }

    public function isAllowedTranslationLocale($locale)
    {
        return true;
    }

    public function getAllNonTranslatables()
    {
        return array_merge(
            $this->getNonTranslatableAttachmentCollections(),
            $this->getNonTranslatablePivots(),
            $this->getNonTranslatables()
        );
    }

    public function getNonTranslatableAttachmentCollections()
    {
        return property_exists($this, 'non_translatable_attachment_collections') ? $this->non_translatable_attachment_collections : [];
    }

    public function getNonTranslatablePivots()
    {
        return property_exists($this, 'non_translatable_pivots') ? $this->non_translatable_pivots : [];
    }

    public function isNonTranslatablePivot($relation)
    {
        return in_array($relation, $this->getNonTranslatablePivots());
    }

    public function isNonTranslatableAttachmentCollection($collection)
    {
        return in_array($collection, $this->getNonTranslatableAttachmentCollections());
    }
}
