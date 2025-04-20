<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('type');

            $table->text('title');
            $table->string('slug');
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->unsignedInteger('menu_order')->index()->default(0);
            $table->string('status')->index();
            $table->dateTime('published_at')->index();
            $table->timestamps();
            $table->softDeletes();

            // Feature values - Always nullable
            $table->string('document_no')->index()->nullable();
            $table->dateTime('expire_at')->index()->nullable();
            $table->string('format')->index()->nullable();
            $table->text('video_url')->nullable();
            $table->string('page_style')->nullable();
            $table->string('ref_no')->index()->nullable();
//            $table->foreignId('sidebar_menu_id')->nullable()->constrained('menus')->nullOnDelete();
            $table->boolean('recently_updated')->index()->default(false);
            // Coordinates
            $table->text('coords')->nullable();
            $table->foreignId('city_id')->nullable()->index();

            $table->jsonTranslatable();

            // slugs must be unique for the category
            $table->unique(['slug', 'type'], 'unique_type_slug');

            $table->foreign('type')
                ->references('slug')
                ->on('post_types')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        // Full Text Index
        if (! app()->runningUnitTests()) {
            DB::statement("ALTER TABLE posts ADD FULLTEXT fulltext_index (`title`, `content`)");
        }
    }

    public function down()
    {
        Schema::dropIfExists('posts');
    }
};
