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
        $tableName = config('cms.database.tag_model', 'tag_model');
        $tagsTable = config('cms.database.tags', 'tags');

        Schema::create($tableName, function (Blueprint $table) use ($tagsTable) {
            $table->id();
            $table->unique(['model_id', 'model_type', 'tag_id'], 'unique_model_tag');
            $table->morphs('model');

            $table->foreignId('tag_id');
            $table->timestamps();

            $table->foreign('tag_id')
                ->references('id')
                ->on($tagsTable)
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
        Schema::dropIfExists(config('cms.database.tag_model', 'tag_model'));
    }
};
