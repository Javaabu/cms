<?php

namespace Javaabu\Cms\Translatable;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Javaabu\Cms\Enums\Languages;

trait IsJsonTranslatable
{
    use IsTranslatable;

    protected $fallback_translations = true;

    public static function bootIsJsonTranslatable()
    {
        static::creating(function ($model) {
            if (! $model->lang) {
                $model->lang = app()->getLocale();
            }
        });
    }

    public function showTranslationFallbacks()
    {
        $this->fallback_translations = true;
    }

    public function dontShowTranslationFallbacks()
    {
        $this->fallback_translations = false;
    }

    public function getAttribute($key)
    {
        if (is_array($key)) {
            return array_map(function ($k) {
                return parent::__get($k);
            }, $key);
        }

        if ($this->isTranslatable($key)) {
            return $this->translate($key, null, $this->shouldFallbackForTranslations());
        }

        [$field, $locale] = $this->getFieldAndLocale($key);

        if ($locale && $this->isTranslatable($field)) {
            return $this->translate($field, $locale, false);
        }

        if (($attr = parent::getAttribute($key)) !== null) {
            return $attr;
        }

        return null;
    }

    public function scopeTranslationsSearch($query, $field, $search, $locale = null)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        $query->where(function ($query) use ($search, $field) {
            $query->whereRaw('lower(json_unquote(json_extract(`translations`, \'$."' . $field . '"\'))) like ?', ['%' . strtolower($search) . '%'])
                ->orWhere($field, 'like', '%' . $search . '%');
        });

        return $query;
    }

    public function scopeOfLocale($query, ?string $locale = null)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        return $query->where('lang', $locale)
            ->orWhereNotNull('translations');
    }

    public function scopeNotHiddenOfLocale($query, ?string $locale = null)
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

    public function translateField($field)
    {
        return $this->translate($field);
    }

    public function translate($field, $locale = null, $fallback = true)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        if ($this->isPrimaryLocale($locale)) {
            $value = parent::getAttribute($field);
        } else {
            $value = $this->translations[$field] ?? null;

            if ($value && $this->hasCast($field) && is_string($value)) {
                $value = $this->castAttribute($field, $value);
            }

            if (empty($value) && $fallback) {
                $value = parent::getAttribute($field);
            }
        }

        return $value;
    }

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

    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function isTranslatable($field)
    {
        return in_array($field, $this->getTranslatables());
    }

    public function getTranslatables()
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }

    public function shouldFallbackForTranslations()
    {
        return $this->fallback_translations;
    }

    public function getFieldAndLocale($attribute)
    {
        $locale = Str::afterLast($attribute, '_');

        if (empty($locale) || (! Languages::isValidKey($locale))) {
            return [$attribute, null];
        }

        $field = Str::beforeLast($attribute, '_');

        return [$field, $locale];
    }

    public function getNonTranslatables()
    {
        $all_fields = Schema::getColumnListing($this->getTable());
        $hide = array_merge($this->getTranslatables(), $this->getFieldsIgnoredForTranslation());

        return array_values(array_diff($all_fields, $hide));
    }

    public function clearTranslations()
    {
        $this->translations = null;
    }

    public function getAdminUrlAttribute(): string
    {
        return $this->getAdminLocalizedUrl();
    }

    public function getAdminLocalizedUrl($locale = null)
    {
        return $this->url('show', $locale);
    }

    public function url(string $action = 'show', ?string $locale = null, string $namespace = 'admin'): string
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        $controller = Str::lower(Str::plural(Str::kebab(class_basename(get_class($this)))));
        $controller_action = $namespace . '.' . $controller . '.' . $action;
        $params = [$locale ?: app()->getLocale()];

        if (! in_array($action, ['index', 'store', 'create', 'trash'])) {
            $params[] = $this->id;
        }

        return URL::route($controller_action, $params);
    }

    public function getLocalizedUrl($locale = null)
    {
        return $this->translatedPermalink('show', $locale);
    }

    public function fillTranslations(array $translations, $locale = null)
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        foreach ($translations as $field => $value) {
            if ($this->isTranslatable($field)) {
                $this->setTranslation($field, $locale, $value);
            } else {
                [$key, $key_locale] = $this->getFieldAndLocale($field);

                if ($key_locale && $this->isTranslatable($key)) {
                    $this->setTranslation($key, $key_locale, $value);
                }
            }
        }

        return $this;
    }

    public function setTranslation($field, $locale, $translation)
    {
        if (! $this->isTranslatable($field) || ! $this->isAllowedTranslationLocale($locale)) {
            return;
        }

        if ($this->isPrimaryLocale($locale)) {
            parent::setAttribute($field, $translation);
        } else {
            $this->setTranslationAttributeValue($field, $locale, $translation);

            if ((! $this->exists) && (! ($this->attributes[$field] ?? null))) {
                parent::setAttribute($field, $translation);
            }
        }
    }

    public function setAttribute($key, $value)
    {
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        }

        if ($this->isTranslatable($key)) {
            $this->setTranslation($key, app()->getLocale(), $value);
            return $this;
        }

        [$field, $locale] = $this->getFieldAndLocale($key);

        if ($locale && $this->isTranslatable($field)) {
            $this->setTranslation($field, $locale, $value);
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function setTranslationAttributeValue($attribute, $locale, $translation)
    {
        $translations = Arr::wrap($this->translations);
        $translations[$attribute] = $translation;
        $this->translations = $translations;
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        return $this->addTranslatableAttributesToArray($attributes);
    }

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

    public function getCurrentLangAttribute()
    {
        $locale = app()->getLocale();

        if ($this->hasTranslations($locale)) {
            return $locale;
        }

        return $this->lang;
    }

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

    public function getTranslationUrlAttribute()
    {
        return $this->getAdminLocalizedEditUrl($this->lang->value == Languages::EN->value ? Languages::DV->value : Languages::EN->value);
    }

    public function getAdminLocalizedEditUrl($locale = null)
    {
        return $this->url('edit', $locale);
    }

    public function getTranslation($locale)
    {
        if ($this->lang->value == $locale) {
            return $this;
        }

        if ($this->lang->value != $locale && $this->translations) {
            return true;
        }
    }

    public function isTranslationHidden($locale)
    {
        return $this->lang->value != $locale && $this->hide_translation;
    }

    public function fill(array $attributes)
    {
        $fillable_translatables = $this->fillableTranslatablesFromArray($attributes);

        foreach ($fillable_translatables as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return parent::fill($attributes);
    }

    protected function fillableTranslatablesFromArray(array $attributes)
    {
        return array_intersect_key($attributes, array_flip($this->getFillableTranslatables()));
    }

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
        return $this->lang->value != app()->getLocale();
    }

    protected function mutateAttributeForArray($key, $value)
    {
        [$field, $locale] = $this->getFieldAndLocale($key);

        if ($locale && $this->isTranslatable($field)) {
            return $this->{$key};
        }

        return parent::mutateAttributeForArray($key, $value);
    }
}
