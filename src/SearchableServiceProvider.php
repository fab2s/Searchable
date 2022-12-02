<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use fab2s\Searchable\Command\Enable;
use fab2s\Searchable\Command\StopWords;
use Illuminate\Support\ServiceProvider;

class SearchableServiceProvider extends ServiceProvider
{
    protected array $commands = [
        StopWords::class,
        Enable::class,
    ];

    /**
     * Register the service provider.
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }
}
