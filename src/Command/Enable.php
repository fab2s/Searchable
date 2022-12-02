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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->index        = (bool) $this->option('index');
        $this->modelRootDir = $this->option('root');
        if (empty($this->modelRootDir)) {
            $this->modelRootDir = app_path('app/Models');
        }

        if (!is_dir($this->modelRootDir)) {
            $this->warn('You must specify an existing directory to look for models');

            return 1;
        }

        $this->getModelFiles();
        $foundSome = false;
        foreach (get_declared_classes() as $fqn) {
            if (in_array(Searchable::class, class_uses_recursive($fqn), true)) {
                $this->output->info("Processing $fqn");
                $foundSome = true;
                /** @var Model&Searchable $instance */
                $instance        = new $fqn;
                $searchableField = $instance->getSearchableField();
                $table           = $instance->getTable();
                $connection      = $instance->getConnectionName();
                $dbType          = $instance->getSearchableFieldDbType();
                $dbSize          = $instance->getSearchableFieldDbSize();

                if (!Schema::connection($connection)->hasColumn($table, $searchableField)) {
                    $this->output->info("Adding $searchableField to $table on $connection");
                    $after = null;
                    if ($instance->usesTimestamps()) {
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
                }

                if ($this->index) {
                    $this->index($instance);
                }
            }
        }

        if ($foundSome) {
            $this->output->success('Done');
        } else {
            $this->warn('Could not find any model using Searchable trait in ' . $this->modelRootDir);
        }

        return 0;
    }

    /**
     * @param Model $instance
     */
    protected function index(Model $instance)
    {
        /** @var Searchable $instance */
        $searchableField = $instance->getSearchableField();
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
