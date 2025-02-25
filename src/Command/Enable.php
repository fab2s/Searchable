<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Command;

use fab2s\Searchable\Traits\Searchable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;

class Enable extends Command
{
    public const CHUNK_SIZE = 500;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchable:enable
            { --root= : The place where to start looking for models, defaults to Laravel\'s app/Models }
            { --model= : To narrow to a single App/Model/ClassName by FQN }
            { --index : To also index / re index }
            { --s|chunkSize= : To adjust chunk size, default is 500 }
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
    protected bool $progress;

    /**
     * @var int
     */
    protected mixed $chunkSize = self::CHUNK_SIZE;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->index        = (bool) $this->option('index');
        $this->modelRootDir = $this->option('root')  ?? app_path('Models');
        $this->model        = $this->option('model') ?? '';
        $this->progress     = (bool) $this->option('progress');
        $this->chunkSize    = max(0, (int) $this->option('chunkSize')) ?: self::CHUNK_SIZE;

        if ($this->model) {
            $this->model = str_replace('/', '\\', $this->model);
            if (! class_exists($this->model)) {
                $this->error('Provided Model FQN not found: ' . $this->model);

                return self::FAILURE;
            }

            if (! $this->handleModel($this->model)) {
                $this->error('Provided Model FQN does not implement Searchable');

                return self::FAILURE;
            }

            $this->output->success('Done');

            return self::SUCCESS;
        }

        if (! is_dir($this->modelRootDir)) {
            $this->warn('You must specify an existing directory to look for models');

            return self::FAILURE;
        }

        $this->getModelFiles();
        $report = [
            'found'  => [],
            'missed' => [],
        ];

        foreach (get_declared_classes() as $fqn) {
            if ($this->handleModel($fqn)) {
                $report['found'][$fqn] = class_basename($fqn);

                continue;
            }

            $report['missed'][$fqn] = class_basename($fqn);
        }

        if (empty($report['found'])) {
            $this->warn('Could not find any model using Searchable trait in ' . $this->modelRootDir);

            return self::FAILURE;
        }

        $this->output->info('Processed: ' . count($report['found']) . ' models');
        $this->output->info(implode(',', $report['found']));

        if (! empty($report['missed'])) {
            $this->output->warning('Missed: ' . count($report['missed']) . ' models');
            $this->output->info(implode(',', $report['missed']));
        }

        $this->output->success('Done');

        return self::SUCCESS;
    }

    public function handleModel(string $fqn): bool
    {
        $foundSome = false;
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

        return $foundSome;
    }

    protected function configureModel(Model $model): static
    {
        /** @var Model&Searchable $model */
        $searchableField = $model->getSearchableField();
        $table           = $model->getTable();
        $connection      = $model->getConnectionName();
        $dbType          = $model->getSearchableFieldDbType();
        $dbSize          = $model->getSearchableFieldDbSize();

        if (! Schema::connection($connection)->hasColumn($table, $searchableField)) {
            $this->output->info("Adding $searchableField to $table");
            $after = null;
            if ($model->usesTimestamps()) {
                $columns = Schema::connection($connection)->getColumnListing($table);
                $after   = $this->getFirstBeforeAtField($columns);
            }

            Schema::connection($connection)->table($table, function (Blueprint $table) use ($searchableField, $after, $dbType, $dbSize, $model) {
                $this->output->info("Using spec $dbType $dbSize");
                if ($after) {
                    $this->output->info("After $after");
                    $table->$dbType($searchableField, $dbSize)->default('')->after($after);
                } else {
                    $table->$dbType($searchableField, $dbSize)->default('');
                }

                $this->output->info('Create full text index');
                $table->fullText($searchableField, Str::snake(class_basename($model)) . '_searchable');
            });
        } else {
            $this->output->info("Found $searchableField in $table");
        }

        return $this;
    }

    protected function index(Model $instance): static
    {
        /** @var Searchable&Model $instance */
        $searchableField = $instance->getSearchableField();
        $count           = $instance->query()->count();
        if ($count <= 0) {
            $this->output->info('Nothing to index for ' . class_basename($instance::class) . ' model');

            return $this;
        }

        $this->output->info(
            'Indexing '
            . number_format($count, 0, ' ')
            . ' rows for ' . class_basename($instance::class)
            . ' model',
        );

        $progressBar = null;
        if ($this->progress) {
            $progressBar = $this->output->createProgressBar($count);

            $progressBar->setFormat(ProgressBar::FORMAT_VERY_VERBOSE);
            $progressBar->start();
        }

        $instance->query()
            ->chunk(
                $this->chunkSize,
                function (Collection $collection) use ($searchableField, $progressBar) {
                    $collection->each(function (Model $model) use ($searchableField) {
                        /* @var  Model&Searchable $record */
                        $record->{$searchableField} = $record->getSearchableContent();
                        $record->save();
                    });

                    if ($this->progress) {
                        $progressBar?->advance($collection->count());
                    }
                },
            )
        ;

        $progressBar?->finish();

        return $this;
    }

    protected function getModelFiles(): static
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

        return $this;
    }

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
