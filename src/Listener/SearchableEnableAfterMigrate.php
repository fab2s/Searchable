<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Listener;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;

class SearchableEnableAfterMigrate
{
    public function handle(MigrationsEnded $event): void
    {
        if (
            $event->method === 'up'
            && ! (isset($event->options['pretend']) && $event->options['pretend'])
        ) {
            Artisan::call('searchable:enable', [], new ConsoleOutput);
        }
    }
}
