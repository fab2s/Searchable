<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\Command\Enable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionException;
use ReflectionMethod;
use Throwable;

class EnableTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->string('field1')->default('');
            $table->string('field2')->default('');
            $table->string('searchable', 500)->default('');
            $table->timestamps();
        });
    }

    /**
     * @throws ReflectionException
     */
    public function test_get_first_before_at_field(): void
    {
        $command = new Enable;
        $method  = new ReflectionMethod($command, 'getFirstBeforeAtField');

        $this->assertSame('email', $method->invoke($command, ['id', 'name', 'email', 'created_at', 'updated_at']));
        $this->assertSame('id', $method->invoke($command, ['id', 'created_at']));
        $this->assertSame('created_at', $method->invoke($command, ['created_at']));
        $this->assertSame('name', $method->invoke($command, ['id', 'name']));
        $this->assertNull($method->invoke($command, []));
    }

    public function test_command_with_nonexistent_model(): void
    {
        $this->artisan('searchable:enable', ['--model' => 'NonExistent\\ClassName'])
            ->assertExitCode(1)
        ;
    }

    public function test_command_with_nonexistent_root(): void
    {
        $this->artisan('searchable:enable', ['--root' => '/nonexistent/path'])
            ->assertExitCode(1)
        ;
    }

    public function test_configure_model_adds_column(): void
    {
        Schema::drop('models');
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->string('field1')->default('');
            $table->string('field2')->default('');
            $table->timestamps();
        });

        $this->assertFalse(Schema::hasColumn('models', 'searchable'));

        try {
            Artisan::call('searchable:enable', ['--model' => Model::class]);
        } catch (Throwable) {
            // FULLTEXT index creation is not supported on SQLite
        }

        $this->assertTrue(Schema::hasColumn('models', 'searchable'));
    }

    public function test_index(): void
    {
        DB::table('models')->insert([
            ['field1' => 'John', 'field2' => 'Doe', 'searchable' => '', 'created_at' => now(), 'updated_at' => now()],
            ['field1' => 'Jane', 'field2' => 'Smith', 'searchable' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('searchable:enable', ['--model' => Model::class, '--index' => true])
            ->assertExitCode(0)
        ;

        $this->assertSame('john doe', DB::table('models')->where('id', 1)->value('searchable'));
        $this->assertSame('jane smith', DB::table('models')->where('id', 2)->value('searchable'));
    }

    public function test_index_phonetic(): void
    {
        DB::table('models')->insert([
            ['field1' => 'John', 'field2' => 'Smith', 'searchable' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('searchable:enable', ['--model' => PhoneticModel::class, '--index' => true])
            ->assertExitCode(0)
        ;

        $this->assertSame('john smith jn sm0', DB::table('models')->where('id', 1)->value('searchable'));
    }
}
