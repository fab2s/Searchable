<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use fab2s\Searchable\Command\Enable;
use fab2s\Searchable\Listener\SearchableEnableAfterMigrate;
use Illuminate\Console\Command;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
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

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            Event::listen(MigrationsEnded::class, SearchableEnableAfterMigrate::class);
        }
    }
}
