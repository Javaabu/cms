<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Javaabu\Cms\Enums\JsonTranslatable\JsonTranslatableSchema;

class CreatePostTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('singular_name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->foreignId('category_type_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->string('og_description')->nullable();
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
            JsonTranslatableSchema::columns($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_types');
    }
}
