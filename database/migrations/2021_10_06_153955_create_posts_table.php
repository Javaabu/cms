<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Javaabu\Cms\Enums\JsonTranslatable\JsonTranslatableSchema;
use Javaabu\Cms\Helpers\PostTypeSchema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            if (Schema::hasTable('departments')) {
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            }

            // Core content fields
            $table->text('title');
            $table->string('slug');
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();

            // Publishing & ordering
            $table->unsignedInteger('menu_order')->index()->default(0);
            $table->string('status')->index()->default('draft');
            $table->dateTime('published_at')->nullable()->index();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Optional feature fields - Always nullable
            $table->string('document_no')->index()->nullable();
            $table->dateTime('expire_at')->index()->nullable();
            $table->string('format')->index()->nullable();
            $table->text('video_url')->nullable();
            $table->string('page_style')->nullable();
            $table->string('ref_no')->index()->nullable();
            $table->string('gazette_link')->nullable();

            if (Schema::hasTable('menus')) {
                $table->foreignId('sidebar_menu_id')->nullable()->constrained('menus')->nullOnDelete();
            }

            $table->boolean('recently_updated')->index()->default(false);
            $table->dateTime('last_updated_at')->nullable();

            // SEO fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();

            // slugs must be unique per post type
            $table->unique(['slug', 'type'], 'unique_type_slug');

            $table->foreign('type')
                  ->references('slug')
                  ->on('post_types')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            JsonTranslatableSchema::columns($table);
        });

        PostTypeSchema::index('posts');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
