<?php

namespace Javaabu\Cms\Models;

use Javaabu\Cms\Enums\JsonTranslatable\JsonTranslatable;
use Javaabu\Cms\Enums\JsonTranslatable\IsJsonTranslatable;

class TranslatableTag extends Tag implements JsonTranslatable
{
    use IsJsonTranslatable;

    /**
     * Constructor
     */
    public function __construct(array $attributes = [])
    {
        $this->mergeFillable(['lang']);
        $this->mergeCasts([
            'translations' => 'array',
            'lang' => \Javaabu\Cms\Enums\Languages::class,
        ]);
        parent::__construct($attributes);
    }

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = [
        'name',
    ];

    /**
     * Get the slug attribute
     *
     * @return string
     */
    public function getSlugAttribute(): string
    {
        return str($this->translate('name', 'en'))->slug();
    }
}
