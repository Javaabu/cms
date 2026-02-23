<?php
/**
 * Methods that translatable models should have
 */

namespace Javaabu\Cms\Enums\JsonTranslatable;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Javaabu\Cms\Enums\Languages;

trait IsJsonTranslatable
{
    use IsTranslatable;

    /**
     * @var boolean
     */
    protected $fallback_translations = true;

    /**
     * Boot function from laravel.
     */
    public static function bootIsJsonTranslatable()
    {
        static::creating(function ($model) {
            if (! $model->lang) {
                $model->lang = app()->getLocale();
            }
        });
    }

    /**
     * Set to show translation fallbacks
     *
     * @return void
     */
    public function showTranslationFallbacks()
    {
        $this->fallback_translations = true;
    }

    /**
     * Set to not show translation fallbacks
     *
     * @return void
     */
    public function dontShowTranslationFallbacks()
    {
        $this->fallback_translations = false;
    }

    public function getAttribute($key)
    {
        // Add support for compoships
        if (is_array($key)) { //Check for multi-columns relationship
            return array_map(function ($k) {
                return parent::__get($k);
            }, $key);
        }

        // translate if translatable
        if ($this->isTranslatable($key)) {
            return $this->translate($key, null, $this->shouldFallbackForTranslations());
        }

        // check if is a suffixed attribute
        [$field, $locale] = $this->getFieldAndLocale($key);

        if ($locale && $this->isTranslatable($field)) {
            return $this->translate($field, $locale, false);
        }

        // fallback to parent
        if (($attr = parent::getAttribute($key)) !== null) {
            return $attr;
        }

        return null;
    }

    /**
     * A search scope
     *
     * @param        $query
     * @param        $field
     * @param        $search
     * @param null $locale
     * @return mixed
     */
    public function scopeTranslationsSearch($query, $field, $search, $locale = null)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        // case insensitive search https://sarav.co/case-insensitive-search-in-mysql-json-columns
        $query->where(function ($query) use ($search, $locale, $field) {
            $query->whereRaw('lower(json_unquote(json_extract(`translations`, \'$."' . $field . '"\'))) like ?', ['%' . strtolower($search) . '%'])
                  ->orWhere($field, 'like', '%' . $search . '%');
        });

        return $query;
    }

    /**
     * Locale scope to return where lang == current locale
     *
     * @param               $query
     * @param string|null $locale
     * @return mixed
     */
    public function scopeOfLocale($query, string $locale = null)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        return $query->where('lang', $locale)
                     ->orWhereNotNull('translations');
    }

    public function scopeNotHiddenOfLocale($query, string $locale = null)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        return $query->where('lang', $locale)
                     ->orWhere(function ($query) {
                         return $query->whereNotNull('translations')
                                      ->where('hide_translation', false);
                     });
    }

    /**
     * Translate the given field to given locale.
     * Fall back to default if no translation
     *
     * @param $field
     * @return string
     */
    public function translateField($field)
    {
        return $this->translate($field);
    }

    /**
     * Translate the given field to given locale.
     * Fall back to default if no translation
     *
     * @param        $field
     * @param null $locale
     * @param bool $fallback
     * @return string
     */
    public function translate($field, $locale = null, $fallback = true)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        if ($this->isPrimaryLocale($locale)) {
            // for default locale, use the direct field value
            $value = parent::getAttribute($field);
        } else {
            // for other locales, use translations
            $value = $this->translations[$field] ?? null;

            if ($value && $this->hasCast($field) && is_string($value)) {
                $value = $this->castAttribute($field, $value);
            }

            // fallback to default if translation missing
            if (empty($value) && $fallback) {
                $value = parent::getAttribute($field);
            }
        }

        return $value;
    }

    /**
     * Check if is primary locale
     *
     * @param null $locale
     * @return bool
     */
    public function isPrimaryLocale($locale = null)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        if (! $locale instanceof Languages) {
            if (Languages::isValidKey($locale)) {
                $locale = Languages::from($locale);
            } else {
                $locale = Languages::EN;
            }
        }

        return empty($this->lang) || $this->lang->value == $locale->value;
    }

    /**
     * Get an attribute from the model.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        // Add support for compoships
        if (is_array($key)) { //Check for multi-columns relationship
            return array_map(function ($k) {
                return parent::__get($k);
            }, $key);
        }

        // translate if translatable
        if ($this->isTranslatable($key)) {
            return $this->translate($key, null, $this->shouldFallbackForTranslations());
        }

        // check if is a suffixed attribute
        [$field, $locale] = $this->getFieldAndLocale($key);

        if ($locale && $this->isTranslatable($field)) {
            return $this->translate($field, $locale, false);
        }

        // fallback to parent
        if (($attr = parent::getAttribute($key)) !== null) {
            return $attr;
        }

        return null;
    }

    /**
     * Check whether the given field is translatable
     *
     * @param string $field
     * @return boolean
     */
    public function isTranslatable($field)
    {
        return in_array($field, $this->getTranslatables());
    }

    /**
     * Get the translatable fields
     *
     * @return array
     */
    public function getTranslatables()
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }

    /**
     * Check whether to show translation fallbacks
     *
     * @return boolean
     */
    public function shouldFallbackForTranslations()
    {
        return $this->fallback_translations;
    }

    /**
     * Get the field and locale for magic attribute
     *
     * @param string $attribute
     * @return array [field, locale]
     */
    public function getFieldAndLocale($attribute)
    {
        $locale = Str::afterLast($attribute, '_');

        if (empty($locale) || (! Languages::isValidKey($locale))) {
            return [$attribute, null];
        }

        $field = Str::beforeLast($attribute, '_');

        return [$field, $locale];
    }

    /**
     * Get the non translatable fields
     *
     * @return array
     */
    public function getNonTranslatables()
    {
        $all_fields = Schema::getColumnListing($this->getTable());

        $hide = array_merge($this->getTranslatables(), $this->getFieldsIgnoredForTranslation());

        return array_values(array_diff($all_fields, $hide));
    }

    /**
     * Clear the translations for the given locale or all
     *
     */
    public function clearTranslations()
    {
        $this->translation = null;
    }

    /**
     * Get the admin url attribute
     *
     * @return string
     */
    public function getAdminUrlAttribute() :string
    {
        return $this->getAdminLocalizedUrl();
    }

    /**
     * Get the admin localized url
     *
     * @param null $locale
     * @return string
     */
    public function getAdminLocalizedUrl($locale = null)
    {
        return $this->url('show', $locale);
    }

    /**
     * Returns the url
     *
     * @param string $action
     * @param string|null $locale
     * @param string $namespace
     * @return string
     */
    public function url(string $action = 'show', string $locale = null, string $namespace = 'admin'): string
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        $controller = Str::lower(Str::plural(Str::kebab(class_basename(get_class($this)))));
        $controller_action = $namespace . '.' . $controller . '.' . $action;

        $params = [];

        $params[] = $locale ?: app()->getLocale();

        if (! in_array($action, ['index', 'store', 'create', 'trash'])) {
            $params[] = $this->id;
        }

        $url = URL::route($controller_action, $params);

        return $url;
    }

    /**
     * Get the localized url
     *
     * Is this for web?
     *
     * @param null $locale
     * @return string
     */
    public function getLocalizedUrl($locale = null)
    {
        return $this->translatedPermalink('show', $locale);
    }

    /**
     * Fill translations in bulk
     *
     * @param array $translations
     * @param null $locale
     * @return mixed
     */
    public function fillTranslations(array $translations, $locale = null)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        foreach ($translations as $field => $value) {
            // check whether the attribute is translatable
            if ($this->isTranslatable($field)) {
                $this->setTranslation($field, $locale, $value);
            } else {
                // check whether the attribute is suffixed
                [$key, $key_locale] = $this->getFieldAndLocale($field);

                if ($key_locale && $this->isTranslatable($key)) {
                    $this->setTranslation($key, $key_locale, $value);
                }
            }
        }

        return $this;
    }

    /**
     * Set translation for given attribute name
     *
     * @param string $field
     * @param string $locale
     * @param string $translation
     * @return void
     */
    public function setTranslation($field, $locale, $translation)
    {
        if (! $this->isTranslatable($field)) {
            return;
        }

        if (! $this->isAllowedTranslationLocale($locale)) {
            return;
        }

        if ($this->isPrimaryLocale($locale)) {
            // directly save to the db field value if default locale
            parent::setAttribute($field, $translation);
        } else {
            // for other locales, save to the translations
            $this->setTranslationAttributeValue($field, $locale, $translation);

            // if it's a new model and the default value is not set, set the default
            if ((! $this->exists) && (! ($this->attributes[$field] ?? null))) {
                parent::setAttribute($field, $translation);
            }
        }
    }

    /**
     * Sets attribute value
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {

        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        }

        // Save the translation if is translatable
        if ($this->isTranslatable($key)) {
            $this->setTranslation($key, app()->getLocale(), $value);
            return $this;
        }

        // check if is a suffixed attribute
        [$field, $locale] = $this->getFieldAndLocale($key);

        if ($locale && $this->isTranslatable($field)) {
            $this->setTranslation($field, $locale, $value);
            return $this;
        }

        // parent call.
        return parent::setAttribute($key, $value);
    }


    /**
     * Model Override functions
     * -------------------------.
     */

    /**
     * Set translation attribute value
     *
     * @param          $attribute
     * @param          $locale
     * @param string $translation
     */
    public function setTranslationAttributeValue($attribute, $locale, $translation)
    {
        $translations = Arr::wrap($this->translations);

        $translations[$attribute] = $translation;

        $this->translations = $translations;
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        $attributes = $this->addTranslatableAttributesToArray($attributes);

        return $attributes;
    }

    /**
     * Add the translatable attributes to the attributes array.
     *
     * @param array $attributes
     * @return array
     */
    protected function addTranslatableAttributesToArray(array $attributes)
    {
        foreach ($this->getTranslatables() as $key) {
            if (! isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = $this->translate($key);
        }

        return $attributes;
    }

    /**
     * Mutate a translated attribute value
     *
     * @param string $field
     * @param mixed $value
     */
    public function mutateTranslationAttributeValue($field, $value)
    {
        if ($this->isJsonCastable($field) && $value) {
            $value = $this->castAttributeAsJson($field, $value);
        }

        if (! $this->isTranslatable($field)) {
            $this->attributes[$field] = $value;
            return;
        }

        $locale = app()->getLocale();

        if ($this->isDefaultTranslationLocale($locale)) {
            $this->attributes[$field] = $value;
        } else {
            $this->setTranslationAttributeValue($field, $locale, $value);

            // if it's a new model and the default value is not set, set the default
            if ((! $this->exists) && (! parent::getAttribute($value))) {
                $this->attributes[$field] = $value;
            }
        }
    }

    /**
     * Get the current lang for the record
     *
     * @return string
     */
    public function getCurrentLangAttribute()
    {
        $locale = app()->getLocale();

        if ($this->hasTranslations($locale)) {
            return $locale;
        }

        return $this->lang;
    }

    /**
     * Check if has Translations
     *
     * @param null $locale
     * @return bool
     */
    public function hasTranslations($locale = null)
    {
        if (! $locale) {
            $locale = Languages::getOppositeLocale();
        }

        if ($this->lang->value == $locale) {
            return true;
        }

        return ! empty($this->translations);
    }

    /**
     * get translation url
     */
    public function getTranslationUrlAttribute()
    {
        return $this->getAdminLocalizedEditUrl($this->lang->value == Languages::EN->value ? Languages::DV->value : Languages::EN->value);
    }

    /**
     * Get the admin localized edit url
     *
     * @param null $locale
     * @return string
     */
    public function getAdminLocalizedEditUrl($locale = null)
    {
        return $this->url('edit', $locale);
    }

    /**
     *
     * @param $locale
     * @return $this|bool
     */
    public function getTranslation($locale)
    {
        if ($this->lang->value == $locale) {
            return $this;
        }

        if ($this->lang->value  != $locale && $this->translations) {
            return true;
        }
    }

    public function isTranslationHidden($locale)
    {
        return $this->lang->value  != $locale && $this->hide_translation;
    }

    /**
     * First fill the suffixed translatables
     * Then fill the main attributes
     *
     * @param array $attributes
     * @return $this
     *
     * @throws MassAssignmentException
     */
    public function fill(array $attributes)
    {
        $fillable_translatables = $this->fillableTranslatablesFromArray($attributes);

        foreach ($fillable_translatables as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return parent::fill($attributes);
    }

    /**
     * Get the fillable translatable attributes of a given array.
     *
     * @param array $attributes
     * @return array
     */
    protected function fillableTranslatablesFromArray(array $attributes)
    {
        return array_intersect_key($attributes, array_flip($this->getFillableTranslatables()));
    }

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillableTranslatables()
    {
        $translatables = $this->getTranslatables();
        $fillables = $this->getFillable();

        $translatable_fillables = array_intersect($translatables, $fillables);
        $language_codes = Languages::getKeys();

        $suffixed_fillables = [];

        foreach ($translatable_fillables as $fillable) {
            foreach ($language_codes as $code) {
                $suffixed_fillables[] = $fillable . '_' . $code;
            }
        }

        return $suffixed_fillables;
    }

    /**
     * Add appends to translatable fields
     *
     * @param array $rules
     * @return array
     */
    public function addTranslationAppends(array $appends): array
    {
        $language_codes = Languages::getKeys();

        $translatables = $this->getTranslatables();
        $converted_appends = $appends;

        foreach ($translatables as $field) {
            foreach ($language_codes as $code) {
                $converted_appends[$field . '_' . $code] = [$field];
            }
        }

        return $converted_appends;
    }

    public function getIsTranslationAttribute(): bool
    {
        return $this->lang->value  != app()->getLocale();
    }

    /**
     * Get the value of an attribute using its mutator for array conversion.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function mutateAttributeForArray($key, $value)
    {
        // check if is a suffixed attribute
        [$field, $locale] = $this->getFieldAndLocale($key);

        if ($locale && $this->isTranslatable($field)) {
            return $this->{$key};
        }

        return parent::mutateAttributeForArray($key, $value);
    }
}
