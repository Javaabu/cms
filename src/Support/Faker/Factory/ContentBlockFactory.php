<?php


namespace Javaabu\Cms\Support\Faker\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Javaabu\Cms\Support\Faker\EnglishContentBlock;

abstract class ContentBlockFactory extends Factory
{
    public function __construct($count = null, ?Collection $states = null, ?Collection $has = null, ?Collection $for = null, ?Collection $afterMaking = null, ?Collection $afterCreating = null, $connection = null)
    {
        parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection);
    }

    abstract function definition();

    public function getContentBlock()
    {
        return (new EnglishContentBlock(fake(), true))->get();
    }

    public function getLiteContentBlock()
    {
        return (new EnglishContentBlock(fake(), true, true))->get();
    }
}
