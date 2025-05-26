<?php

namespace Javaabu\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Javaabu\Auth\User;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Support\Faker\Factory\ContentBlockFactory;
use Javaabu\Helpers\Enums\PublishStatuses;
use Javaabu\Mediapicker\Models\Attachment;

class PostFactory extends ContentBlockFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'slug' => fake()->slug(),
            'content' => $this->getContentBlock(),
            'excerpt' => fake()->words(2, true),
            'menu_order' => fake()->randomNumber(),
            'status' => fake()->randomElement(PublishStatuses::cases()),
//            'published_at' => Carbon::now(),
//            'created_at' => Carbon::now(),
//            'updated_at' => Carbon::now(),
//            'document_no' => fake()->words(2, true),
//            'expire_at' => Carbon::now(),
//            'format' => fake()->words(2, true),
//            'video_url' => fake()->url(),
//            'page_style' => fake()->words(2, true),
//            'ref_no' => fake()->words(2, true),
//            'recently_updated' => fake()->boolean(),
//            'coords' => fake()->words(2, true),
//            'city_id' => fake()->randomNumber(),
//
            'type' => random_id_or_generate(PostType::class, 'slug'),
        ];
    }
}
