<?php

namespace Javaabu\Cms\Translatable;

use Illuminate\Database\Schema\Blueprint;

class JsonTranslatableSchema
{
    public static function columns(Blueprint $table): void
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
