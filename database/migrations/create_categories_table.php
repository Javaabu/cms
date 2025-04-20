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
        Schema::create('categories', function (Blueprint $table) {
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
            $table->unique(['slug', 'type_id'], 'unique_type_slug');

            $table->foreign('type_id')
                ->references('id')
                ->on('category_types')
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
        Schema::dropIfExists('categories');
    }
};
