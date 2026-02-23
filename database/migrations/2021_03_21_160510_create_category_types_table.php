<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Enums\JsonTranslatable\JsonTranslatableSchema;

class CreateCategoryTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('singular_name');
            $table->string('slug')->unique();
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
        Schema::dropIfExists('category_types');
    }
}
