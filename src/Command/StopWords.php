<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Command;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StopWords extends Command
{
    const STOP_WORDS_TABLE = 'stop_words';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchable:stopwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Maintain Stop words';

    /**
     * @var string
     */
    protected $stopWordDir = 'stopwords';

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     *
     * @return int
     */
    public function handle()
    {
        if (!Schema::hasTable(self::STOP_WORDS_TABLE)) {
            $this->output->info('Create ' . self::STOP_WORDS_TABLE);
            Schema::create(self::STOP_WORDS_TABLE, function (Blueprint $table) {
                $table->string('value', 32);
            });
        }

        $this->output->info('Empty ' . self::STOP_WORDS_TABLE);
        DB::table(self::STOP_WORDS_TABLE)->truncate();
        $this->output->info('Populate ' . self::STOP_WORDS_TABLE);

        DB::table(self::STOP_WORDS_TABLE)->insert($this->getInsertStopWords());

        $this->output->success('Done');

        return 0;
    }

    /**
     * @throws FileNotFoundException
     *
     * @return array
     */
    protected function getInsertStopWords(): array
    {
        $result = [];
        foreach ($this->getStopWords() as $insertStopWord) {
            $result[] = ['value' =>  $insertStopWord];
        }

        return $result;
    }

    /**
     * @throws FileNotFoundException
     *
     * @return \Generator
     */
    protected function getStopWords(): \Generator
    {
        $fs = new Filesystem;
        foreach ($fs->files(__DIR__ . '/../' . $this->stopWordDir) as $file) {
            if ($file->getExtension() !== 'txt') {
                continue;
            }

            foreach ($fs->lines($file->getRealPath()) as $word) {
                yield trim($word);
            }
        }
    }
}
