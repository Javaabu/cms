<?php
/**
 * Methods that JSON translatable models should have
 */

namespace Javaabu\Cms\Enums\JsonTranslatable;

interface JsonTranslatable extends Translatable
{
    /**
     * Fill translations in bulk
     *
     * @param array $translations
     * @param null $locale
     * @return mixed
     */
    public function fillTranslations(array $translations, $locale = null);

    /**
     * Set translation for given attribute name
     *
     * @param string $field
     * @param string $locale
     * @param string $translation
     * @return void
     */
    public function setTranslation($field, $locale, $translation);

    /**
     * Set translation attribute value
     *
     * @param $attribute
     * @param $locale
     * @param string $translation
     */
    public function setTranslationAttributeValue($attribute, $locale, $translation);
}
