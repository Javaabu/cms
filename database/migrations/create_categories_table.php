<?php

use Kalnoy\Nestedset\NestedSet;
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
        $tableName = config('cms.database.categories', 'categories');
        $categoryTypesTable = config('cms.database.category_types', 'category_types');

        Schema::create($tableName, function (Blueprint $table) use ($categoryTypesTable) {
            $table->id();
            $table->foreignId('type_id');
            $table->string('name');
            $table->string('slug');
            $table->string('statistic_type')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('order_column')->default(0)->index();
            NestedSet::columns($table);
            $table->timestamps();

            // slugs must be unique for the category
            $table->unique(['slug', 'type_id'], 'unique_category_slug');

            $table->foreign('type_id')
                ->references('id')
                ->on($categoryTypesTable)
                ->onDelete('cascade');

            $table->jsonTranslatable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('cms.database.categories', 'categories'));
    }
};
