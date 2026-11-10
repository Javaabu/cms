<?php

namespace Javaabu\Cms\Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * javaabu/cms v1's tables, as its migrations created them
 */
trait CreatesV1Tables
{
    protected function createV1Tables(string $prefix = ''): void
    {
        Schema::create("{$prefix}category_types", function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('singular_name');
            $table->string('slug')->unique();
            $table->timestamps();
            $table->json('translations')->nullable();
            $table->string('lang')->nullable();
        });

        Schema::create("{$prefix}categories", function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id');
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('order_column')->default(0);
            $table->unsignedInteger('_lft')->default(0);
            $table->unsignedInteger('_rgt')->default(0);
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamps();
            $table->json('translations')->nullable();
            $table->string('lang')->nullable();
        });

        Schema::create("{$prefix}post_types", function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('singular_name');
            $table->string('slug')->unique();
            $table->string('icon');
            $table->foreignId('category_type_id')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
        });

        Schema::create("{$prefix}posts", function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->text('title');
            $table->string('slug');
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->unsignedInteger('menu_order')->default(0);
            $table->string('status');
            $table->dateTime('published_at');
            $table->timestamps();
            $table->softDeletes();
            $table->string('document_no')->nullable();
            $table->dateTime('expire_at')->nullable();
            $table->string('format')->nullable();
            $table->text('video_url')->nullable();
            $table->string('page_style')->nullable();
            $table->string('ref_no')->nullable();
            $table->boolean('recently_updated')->default(false);
            $table->text('coords')->nullable();
            $table->foreignId('city_id')->nullable();
            $table->json('translations')->nullable();
            $table->string('lang')->nullable();
        });

        Schema::create("{$prefix}category_model", function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->foreignId('category_id');
            $table->timestamps();
        });

        Schema::create("{$prefix}tags", function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create("{$prefix}tag_model", function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->foreignId('tag_id');
            $table->timestamps();
        });
    }
}
