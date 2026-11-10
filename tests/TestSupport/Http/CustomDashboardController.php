<?php

namespace Javaabu\Cms\Tests\TestSupport\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Javaabu\Cms\Http\Controllers\DashboardController;

class CustomDashboardController extends DashboardController
{
    protected function data(Request $request, ?Model $user): array
    {
        return [...parent::data($request, $user), 'greeting' => 'އައްސަލާމު ޢަލައިކުމް'];
    }
}
