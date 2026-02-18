<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\Listener\SearchableEnableAfterMigrate;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Symfony\Component\Console\Output\ConsoleOutput;

class ListenerTest extends TestCase
{
    public function test_runs_enable_after_up_migration(): void
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('call')
            ->once()
            ->with('searchable:enable', [], Mockery::type(ConsoleOutput::class))
        ;
        Artisan::swap($mock);

        (new SearchableEnableAfterMigrate)->handle(new MigrationsEnded('up'));
    }

    public function test_skips_on_down_migration(): void
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('call')->never();
        Artisan::swap($mock);

        (new SearchableEnableAfterMigrate)->handle(new MigrationsEnded('down'));
    }

    public function test_skips_on_pretend(): void
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('call')->never();
        Artisan::swap($mock);

        (new SearchableEnableAfterMigrate)->handle(new MigrationsEnded('up', ['pretend' => true]));
    }

    public function test_runs_when_pretend_is_false(): void
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('call')
            ->once()
            ->with('searchable:enable', [], Mockery::type(ConsoleOutput::class))
        ;
        Artisan::swap($mock);

        (new SearchableEnableAfterMigrate)->handle(new MigrationsEnded('up', ['pretend' => false]));
    }

    public function test_runs_when_options_missing_pretend(): void
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('call')
            ->once()
            ->with('searchable:enable', [], Mockery::type(ConsoleOutput::class))
        ;
        Artisan::swap($mock);

        (new SearchableEnableAfterMigrate)->handle(new MigrationsEnded('up', []));
    }
}
