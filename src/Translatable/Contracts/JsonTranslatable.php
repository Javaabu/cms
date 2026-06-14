<?php

namespace Javaabu\Cms\Translatable\Contracts;

interface JsonTranslatable extends Translatable
{
    public function fillTranslations(array $translations, $locale = null);

    public function setTranslation($field, $locale, $translation);

    public function setTranslationAttributeValue($attribute, $locale, $translation);
}
