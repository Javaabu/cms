<?php

namespace Javaabu\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;

class PostTypeFactory extends Factory
{
    protected $model = PostType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'singular_name' => $this->faker->name(),
            'slug' => $this->faker->slug(),
            'icon' => $this->faker->word(),
            'features' => $this->faker->words(),
            'og_description' => $this->faker->text(),
            'order_column' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'category_type_id' => random_id_or_generate(CategoryType::class),
        ];
    }
}
