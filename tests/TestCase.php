<?php

namespace artisanalbyte\InertiaCrudGenerator\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use artisanalbyte\InertiaCrudGenerator\InertiaCrudGeneratorServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Register package service provider.
     */
    protected function getPackageProviders($app)
    {
        return [InertiaCrudGeneratorServiceProvider::class];
    }

    /**
     * Configure in-memory test DB.
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
