<?php

namespace Javaabu\Cms\Translatable\Contracts;

interface Translatable
{
    public function getFieldsIgnoredForTranslation();

    public function getTranslatables();

    public function getNonTranslatables();

    public function getNonTranslatablePivots();

    public function getNonTranslatableAttachmentCollections();

    public function getAllNonTranslatables();

    public function isNonTranslatablePivot($relation);

    public function isNonTranslatableAttachmentCollection($collection);

    public function translate($field, $locale);

    public function isTranslatable($field);

    public function clearTranslations();

    public function getAdminLocalizedUrl($locale = null);

    public function getLocalizedUrl($locale = null);

    public function url(string $action = 'show', ?string $locale = null, string $namespace = 'admin'): string;

    public function hasTranslations($locale = null);

    public function isDefaultTranslationLocale($locale);

    public function getDefaultTranslationLocale();

    public function getAllowedTranslationLocales();

    public function isAllowedTranslationLocale($locale);
}
