<?php

/*
 * This file is part of fab2s/laravel-dt0.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/laravel-dt0
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SearchQuery
{
    const SEARCHABLE_FIELD = 'searchable';
    protected ?string $order;
    protected string $searchableField = self::SEARCHABLE_FIELD;
    protected string $tsConfig;
    protected bool $phonetic;

    /**
     * Search constructor.
     */
    public function __construct(?string $order = 'DESC', string $searchableField = self::SEARCHABLE_FIELD, string $tsConfig = 'english', bool $phonetic = false)
    {
        $this->order           = $this->getOrder($order);
        $this->searchableField = $searchableField;
        $this->tsConfig        = $tsConfig;
        $this->phonetic        = $phonetic;
    }

    /**
     * @param Builder<Model>           $query
     * @param string|array<int,string> $search
     */
    public function addMatch(Builder $query, string|array $search, string $tableAlias = '', ?string $order = null): void
    {
        $driver = $query->getConnection()->getDriverName(); // @phpstan-ignore method.notFound
        $terms  = TermParser::parse($search, $driver, $this->phonetic);
        if (empty($terms)) {
            return;
        }

        $searchField = ($tableAlias ? "$tableAlias." : '') . $this->searchableField;
        $order       = $order ? $this->getOrder($order) : $this->order;

        if ($driver === 'pgsql') {
            $query->whereRaw("to_tsvector('{$this->tsConfig}', {$searchField}) @@ to_tsquery('{$this->tsConfig}', ?)", [$terms]);

            if ($order) {
                $query->orderByRaw("ts_rank(to_tsvector('{$this->tsConfig}', {$searchField}), to_tsquery('{$this->tsConfig}', ?)) {$order}", [$terms]);
            }

            return;
        }

        $query->whereRaw('MATCH (' . $searchField . ') AGAINST (? IN BOOLEAN MODE)', [$terms]);

        if ($order) {
            $query->orderByRaw('(MATCH (' . $searchField . ') AGAINST (? IN BOOLEAN MODE)) ' . $order, [$terms]);
        }
    }

    public function getOrder(?string $order = null): string
    {
        return $order && in_array(strtoupper($order), ['DESC', 'ASC'], true) ? $order : '';
    }
}
