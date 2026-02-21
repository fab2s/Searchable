<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Command;

use fab2s\Searchable\SearchableInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;

class Enable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchable:enable
            {--root= : The place where to start looking for models, defaults to Laravel\'s app/Models}
            {--model= : To narrow to a single App/Model/ClassName by FQN}
            {--index : To also index / re index}
            { --p|progress : to activate progress bar }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable searchable for your models';
    protected string $modelRootDir;
    protected bool $index;
    protected string $model;
    protected bool $hasProgress = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->index        = (bool) $this->option('index');
        $root               = $this->option('root');
        $this->modelRootDir = is_string($root) ? $root : app_path('Models');
        $model              = $this->option('model');
        $this->model        = is_string($model) ? $model : '';
        $this->hasProgress  = (bool) $this->option('progress');

        if ($this->model) {
            $this->model = str_replace('/', '\\', $this->model);
            if (! str_contains($this->model, '\\')) {
                $this->model = '\\App\\Models\\' . $this->model;
            }

            if (! class_exists($this->model)) {
                $this->error('Provided Model FQN not found: ' . $this->model);

                return self::FAILURE;
            }

            $this->handleModel($this->model);
            $this->comment('Done');

            return self::SUCCESS;
        }

        if (! is_dir($this->modelRootDir)) {
            $this->warn('You must specify an existing directory to look for models');

            return self::FAILURE;
        }

        $this->comment('Searchable start');
        $this->getModelFiles();
        $foundSome = false;
        foreach (get_declared_classes() as $fqn) {
            if ($this->handleModel($fqn)) {
                $foundSome = true;
            }
        }

        if (! $foundSome) {
            $this->warn('Could not find any model using Searchable trait in ' . $this->modelRootDir);
        } else {
            $this->comment('Done');
        }

        return self::SUCCESS;
    }

    public function handleModel(string $fqn): bool
    {
        if (! is_subclass_of($fqn, Model::class) || ! is_subclass_of($fqn, SearchableInterface::class)) {
            return false;
        }

        $this->info("Processing $fqn");
        $instance = new $fqn;
        $this->configureModel($instance);

        if ($this->index) {
            $this->index($instance);
        }

        return true;
    }

    protected function configureModel(Model&SearchableInterface $model): static
    {
        $searchableField = $model->getSearchableField();
        $table           = $model->getTable();
        $connection      = $model->getConnectionName();
        $dbType          = $model->getSearchableFieldDbType();
        $dbSize          = $model->getSearchableFieldDbSize();
        $driver          = DB::connection($connection)->getDriverName();

        if (! Schema::connection($connection)->hasColumn($table, $searchableField)) {
            $this->line("Adding $searchableField to $table");
            $after = null;
            if ($driver !== 'pgsql' && $model->usesTimestamps()) {
                $columns = Schema::connection($connection)->getColumnListing($table);
                $after   = $this->getFirstBeforeAtField($columns);
            }

            Schema::connection($connection)->table($table, function (Blueprint $table) use ($searchableField, $after, $dbType, $dbSize) {
                $this->line("Using spec $dbType $dbSize");
                if ($after) {
                    $this->line("After $after");
                    $table->$dbType($searchableField, $dbSize)->default('')->after($after);
                } else {
                    $table->$dbType($searchableField, $dbSize)->default('');
                }
            });

            $this->line('Create full text index');
            if ($driver === 'pgsql') { // @codeCoverageIgnoreStart
                $tsConfig = $model->getSearchableTsConfig();
                DB::connection($connection)->statement("CREATE INDEX {$table}_{$searchableField}_fulltext ON {$table} USING GIN(to_tsvector('{$tsConfig}', {$searchableField}))");
            } else { // @codeCoverageIgnoreEnd
                DB::connection($connection)->statement("ALTER TABLE $table ADD FULLTEXT searchable($searchableField)");
            }
        } else {
            $this->line("Found $searchableField in $table");
        }

        return $this;
    }

    protected function index(Model&SearchableInterface $instance): void
    {
        $searchableField = $instance->getSearchableField();
        $table           = $instance->getTable();
        $keyName         = $instance->getKeyName();
        $connection      = $instance->getConnectionName();

        $this->info('Indexing: ' . $instance::class);
        $bar = $this->hasProgress ? $this->getOutput()->createProgressBar($instance->count()) : null; // @phpstan-ignore method.notFound
        $bar?->setFormat(ProgressBar::FORMAT_VERY_VERBOSE);
        // @phpstan-ignore method.notFound
        $instance->chunkById(
            1000,
            function (Collection $chunk) use ($bar, $searchableField, $table, $keyName, $connection) {
                /** @var Collection<int, Model&SearchableInterface> $chunk */
                $cases    = [];
                $bindings = [];
                $ids      = [];

                foreach ($chunk as $model) {
                    /** @var Model&SearchableInterface $model */
                    $id         = $model->getKey();
                    $ids[]      = $id;
                    $cases[]    = 'WHEN ? THEN ?';
                    $bindings[] = $id;
                    $bindings[] = $model->getSearchableContent();
                }

                $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
                $bindings       = array_merge($bindings, $ids);

                DB::connection($connection)->update(
                    "UPDATE {$table} SET {$searchableField} = CASE {$keyName} "
                    . implode(' ', $cases)
                    . " END WHERE {$keyName} IN ({$idPlaceholders})",
                    $bindings,
                );

                $bar?->advance($chunk->count());
            },
        );

        $bar?->finish();
        $this->newLine();
    }

    protected function getModelFiles(): void
    {
        $finder = (new Finder)
            ->files()
            ->in($this->modelRootDir)
            ->name('*.php')
            ->getIterator()
        ;

        foreach ($finder as $file) {
            require_once $file->getRealPath();
        }
    }

    /** @param array<int,string> $columns */
    protected function getFirstBeforeAtField(array $columns): ?string
    {
        $prev = null;
        foreach ($columns as $column) {
            if (preg_match('`.+_at$`i', $column)) {
                return $prev ? $prev : $column;
            }

            $prev = $column;
        }

        return $prev;
    }
}
