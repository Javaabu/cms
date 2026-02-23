<?php
/**
 * Columns for Translation models
 */

namespace Javaabu\Cms\Enums\JsonTranslatable;

use Illuminate\Database\Schema\Blueprint;

class JsonTranslatableSchema
{

    /**
     * Adds the columns needed for email verification
     *
     * @param Blueprint $table
     */
    public static function columns(Blueprint $table)
    {
        if (app()->runningUnitTests()) {
            $table->text('translations')->nullable();
        } else {
            $table->json('translations')->nullable();
        }

        $table->string('lang')->index();
        $table->boolean('hide_translation')->index()->default(false);
    }
}
