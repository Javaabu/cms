<?php

namespace Javaabu\Cms\Tests\TestSupport\Policies;

use Illuminate\Database\Eloquent\Model;

class AllowAllPolicy
{
    public function view(Model $user): bool
    {
        return true;
    }
}
