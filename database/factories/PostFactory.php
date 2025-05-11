<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Support\Faker\Factory\ContentBlockFactory;

class PostFactory extends ContentBlockFactory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'slug' => $this->faker->slug(),
            'content' => $this->getContentBlock(),
            'excerpt' => $this->faker->word(),
            'menu_order' => $this->faker->randomNumber(),
            'status' => $this->faker->word(),
            'published_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'document_no' => $this->faker->word(),
            'expire_at' => Carbon::now(),
            'format' => $this->faker->word(),
            'video_url' => $this->faker->url(),
            'page_style' => $this->faker->word(),
            'ref_no' => $this->faker->word(),
            'recently_updated' => $this->faker->boolean(),
            'coords' => $this->faker->word(),
            'city_id' => $this->faker->randomNumber(),

            'type' => random_id_or_generate(PostType::class, 'slug'),
        ];
    }
}
