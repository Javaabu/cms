<?php

namespace Javaabu\Cms\Tests;

use Javaabu\Helpers\HelpersServiceProvider;
use Javaabu\Translatable\TranslatableServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Javaabu\Cms\CmsServiceProvider;
use Javaabu\Cms\Tests\TestSupport\Providers\TestServiceProvider;

abstract class TestCase extends BaseTestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');

        $this->app['config']->set('session.serialization', 'php');

    }

    protected function getPackageProviders($app)
    {
        return [
            CmsServiceProvider::class,
            TranslatableServiceProvider::class,
            HelpersServiceProvider::class,
            TestServiceProvider::class
        ];
    }
}
