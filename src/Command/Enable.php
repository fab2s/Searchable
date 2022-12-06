<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Command;

use fab2s\Searchable\Traits\Searchable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            {--index : To also index / re index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable searchable for your models';

    /**
     * @var string
     */
    protected string $modelRootDir;

    /**
     * @var bool
     */
    protected bool $index;
    protected string $model;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->index        = (bool) $this->option('index');
        $this->modelRootDir = $this->option('root')  ??  app_path('Models');
        $this->model        = $this->option('model') ??  '';

        if ($this->model) {
            $this->model = str_replace('/', '\\', $this->model);
            if (!class_exists($this->model)) {
                $this->error('Provided Model FQN not found: ' . $this->model);

                return 1;
            }

            $this->handleModel($this->model);
            $this->output->success('Done');

            return 0;
        }

        if (!is_dir($this->modelRootDir)) {
            $this->warn('You must specify an existing directory to look for models');

            return 1;
        }

        $this->getModelFiles();
        $foundSome = false;
        foreach (get_declared_classes() as $fqn) {
            $this->handleModel($fqn);
        }

        if (!$foundSome) {
            $this->warn('Could not find any model using Searchable trait in ' . $this->modelRootDir);
        } else {
            $this->output->success('Done');
        }

        return 0;
    }

    public function handleModel(string $fqn): static
    {
        if (in_array(Searchable::class, class_uses_recursive($fqn), true)) {
            $this->output->info("Processing $fqn");
            $foundSome = true;
            /** @var Model&Searchable $instance */
            $instance = new $fqn;
            $this->configureModel($instance);

            if ($this->index) {
                $this->index($instance);
            }
        }

        return $this;
    }

    protected function configureModel(Model $model): static
    {
        /** @var Model&Searchable $model */
        $searchableField = $model->getSearchableField();
        $table           = $model->getTable();
        $connection      = $model->getConnectionName();
        $dbType          = $model->getSearchableFieldDbType();
        $dbSize          = $model->getSearchableFieldDbSize();

        if (!Schema::connection($connection)->hasColumn($table, $searchableField)) {
            $this->output->info("Adding $searchableField to $table");
            $after = null;
            if ($model->usesTimestamps()) {
                $columns = Schema::connection($connection)->getColumnListing($table);
                $after   = $this->getFirstBeforeAtField($columns);
            }

            Schema::connection($connection)->table($table, function (Blueprint $table) use ($searchableField, $after, $dbType, $dbSize) {
                $this->output->info("Using spec $dbType $dbSize");
                if ($after) {
                    $this->output->info("After $after");
                    $table->$dbType($searchableField, $dbSize)->default('')->after($after);
                } else {
                    $table->$dbType($searchableField, $dbSize)->default('');
                }
            });

            $this->output->info('Create full text index');
            DB::connection($connection)->statement("ALTER TABLE $table ADD FULLTEXT searchable($searchableField)");
        } else {
            $this->output->info("Found $searchableField in $table");
        }

        return $this;
    }

    /**
     * @param Model $instance
     */
    protected function index(Model $instance)
    {
        /** @var Searchable $instance */
        $searchableField = $instance->getSearchableField();
        $this->output->info('Indexing: ' . $instance::class);
        $this->output->progressStart();
        foreach ($instance->lazy() as $record) {
            /* @var  Model&Searchable $record */
            $record->{$searchableField} = $record->getSearchableContent();
            $record->save();
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
    }

    protected function getModelFiles()
    {
        $finder = (new Finder)
            ->files()
            ->in($this->modelRootDir)
            ->name('*.php')
            ->getIterator();

        foreach ($finder as $file) {
            require_once $file->getRealPath();
        }
    }

    /**
     * @param array $columns
     *
     * @return string|null
     */
    protected function getFirstBeforeAtField(array $columns): ? string
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
