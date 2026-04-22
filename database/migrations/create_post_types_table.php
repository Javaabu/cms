<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Javaabu\Translatable\JsonTranslatable\JsonTranslatableSchema;

return new class extends Migration
{
    public function up()
    {
        $tableName = config('cms.database.post_types', 'post_types');
        $categoryTypesTable = config('cms.database.category_types', 'category_types');

        Schema::create($tableName, function (Blueprint $table) use ($categoryTypesTable) {
            $table->id();
            $table->string('name');
            $table->string('singular_name');
            $table->string('slug')->unique();
            $table->string('icon');
            $table->foreignId('category_type_id')
                ->nullable()
                ->constrained($categoryTypesTable)
                ->nullOnDelete();
            $table->json('features')->nullable();
            $table->string('og_description')->nullable();
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
            
            if (config('cms.should_translate', false)) {
                $table->jsonTranslatable();
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('cms.database.post_types', 'post_types'));
    }
};
