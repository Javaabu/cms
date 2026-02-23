<?php

namespace Javaabu\Cms\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

class PostTypeSchema
{

    /**
     * Adds the columns needed for post types
     *
     * @param Blueprint $table
     * @param string $name
     */
    public static function columns(Blueprint $table, string $name = 'title')
    {
        $table->id();

        if ($name) {
            $table->text($name);
            $table->string('slug')->unique();
        }

        $table->text('content')->nullable();
        $table->text('excerpt')->nullable();
        $table->unsignedInteger('menu_order')->index()->default(0);
        $table->string('status')->index();
        $table->dateTime('published_at')->index();
        $table->timestamps();
        $table->softDeletes();
    }

    /**
     * Adds the indexes needed for post types
     *
     * @param string $table
     * @param string $name
     */
    public static function index(string $table, $name = 'title')
    {
        // Full Text Index
        if (! app()->runningUnitTests()) {
            DB::statement("ALTER TABLE $table ADD FULLTEXT fulltext_index (`" . $name . "`, `content`)");
        }
    }
}
