<?php

/*
 * This file is part of fab2s/laravel-dt0.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/laravel-dt0
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use fab2s\Searchable\Command\Enable;
use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;

class SearchableServiceProvider extends ServiceProvider
{
    /** @var array<int,class-string<Command>> */
    protected array $commands = [
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
