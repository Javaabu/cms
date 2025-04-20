<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Javaabu\Translatable\JsonTranslatable\JsonTranslatableSchema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('post_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('singular_name');
            $table->string('slug')->unique();
            $table->string('icon');
            $table->foreignId('category_type_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->json('features')->nullable();
            $table->string('og_description')->nullable();
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
            $table->jsonTranslatable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_types');
    }
};
