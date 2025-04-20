<?php

namespace Javaabu\Cms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Javaabu\Cms\Cms
 */
class Cms extends Facade {
    protected static function getFacadeAccessor() {
        return 'cms';
    }
}
