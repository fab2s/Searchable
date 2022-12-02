<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\SearchableServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\ServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get package providers.
     *
     * @param Application $app
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            SearchableServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // make sure, our .env file is loaded
        $app->useEnvironmentPath(dirname(__DIR__));
        $app->bootstrapWith([LoadEnvironmentVariables::class]);
        $this->defineEnvironment($app);
    }
}
