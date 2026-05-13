<?php

namespace Javaabu\Cms\PostTypes;

use Javaabu\Cms\Enums\PostTypeFeatures;

class PostType
{
    protected string $slug;

    protected ?string $name = null;

    protected ?string $nameDv = null;

    protected ?string $singularName = null;

    protected ?string $icon = null;

    protected ?string $categoryType = null;

    protected array $features = [];

    protected ?string $description = null;

    protected ?string $ogDescription = null;

    protected ?int $orderColumn = null;

    public function __construct(string $slug)
    {
        $this->slug = $slug;
    }

    public static function make(string $slug): self
    {
        return new static($slug);
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function nameDv(string $nameDv): self
    {
        $this->nameDv = $nameDv;

        return $this;
    }

    public function singularName(string $singularName): self
    {
        $this->singularName = $singularName;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function categoryType(string $categoryType): self
    {
        $this->categoryType = $categoryType;

        return $this;
    }

    public function feature(string|PostTypeFeatures $feature, bool|string $value = true): self
    {
        $featureKey = $feature instanceof PostTypeFeatures ? $feature->value : $feature;
        $this->features[$featureKey] = $value;

        return $this;
    }

    public function features(array|string|PostTypeFeatures ...$features): self
    {
        foreach ($features as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $arrayKey => $arrayValue) {
                    if (is_int($arrayKey)) {
                        $this->feature($arrayValue, true);
                        continue;
                    }

                    $this->feature($arrayKey, $arrayValue);
                }
                continue;
            }

            if (is_int($key)) {
                $this->feature($value, true);
                continue;
            }

            $this->feature($key, $value);
        }

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function ogDescription(string $ogDescription): self
    {
        $this->ogDescription = $ogDescription;

        return $this;
    }

    public function orderColumn(int $orderColumn): self
    {
        $this->orderColumn = $orderColumn;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'slug' => $this->slug,
            'name' => $this->name,
            'name_dv' => $this->nameDv,
            'singular_name' => $this->singularName,
            'icon' => $this->icon,
            'category_type' => $this->categoryType,
            'features' => $this->features,
            'description' => $this->description,
            'og_description' => $this->ogDescription,
            'order_column' => $this->orderColumn,
        ], static fn ($value) => $value !== null);
    }
}
