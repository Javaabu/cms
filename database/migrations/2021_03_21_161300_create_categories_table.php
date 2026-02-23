<?php

use Javaabu\Cms\Enums\JsonTranslatable\JsonTranslatableSchema;
use Kalnoy\Nestedset\NestedSet;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('statistic_type')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('order_column')->default(0)->index();
            NestedSet::columns($table);
            $table->timestamps();

            // slugs must be unique for the category type
            $table->unique(['slug', 'type_id'], 'unique_type_slug');

            $table->foreign('type_id')
                  ->references('id')
                  ->on('category_types')
                  ->onDelete('cascade');

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
        Schema::dropIfExists('categories');
    }
}
