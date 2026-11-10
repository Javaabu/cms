<?php

namespace Javaabu\Cms\Tests\TestSupport\Http;

use Illuminate\Http\Request;
use Javaabu\Cms\Http\Controllers\HomeController;

class CustomHomeController extends HomeController
{
    protected function data(Request $request): array
    {
        return [...parent::data($request), 'banner' => 'ބެނަރު'];
    }
}
