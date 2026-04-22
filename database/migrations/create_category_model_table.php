<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('cms.database.category_model', 'category_model');
        $categoriesTable = config('cms.database.categories', 'categories');

        Schema::create($tableName, function (Blueprint $table) use ($categoriesTable) {
            $table->id();
            $table->unique(['model_id', 'model_type', 'category_id'], 'unique_model_category');
            $table->morphs('model');
            $table->foreignId('category_id');
            $table->timestamps();

            $table->foreign('category_id')
                  ->references('id')
                  ->on($categoriesTable)
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('cms.database.category_model', 'category_model'));
    }
};
