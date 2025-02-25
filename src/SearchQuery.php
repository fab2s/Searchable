<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use Illuminate\Database\Eloquent\Builder;

class SearchQuery
{
    const SEARCHABLE_FIELD = 'searchable';
    protected ?string $order;
    protected string $searchableField = self::SEARCHABLE_FIELD;

    /**
     * Search constructor.
     */
    public function __construct(?string $order = 'DESC', string $searchableField = self::SEARCHABLE_FIELD)
    {
        $this->order           = $this->getOrder($order);
        $this->searchableField = $searchableField;
    }

    /**
     * @param string|array<int,string> $search
     */
    public function addMatch(Builder $query, string|array $search, string $tableAlias = '', ?string $order = null): static
    {
        $terms = TermParser::parse($search);
        if (empty($terms)) {
            return $this;
        }

        $searchField = ($tableAlias ? "$tableAlias." : '') . $this->searchableField;
        $query->whereRaw('MATCH (' . $searchField . ') AGAINST (? IN BOOLEAN MODE)', [$terms]);
        $order = $order ? $this->getOrder($order) : $this->order;

        if ($order) {
            $query->orderByRaw('(MATCH (' . $searchField . ') AGAINST (? IN BOOLEAN MODE)) ' . $order, [$terms]);
        }

        return $this;
    }

    public function getOrder(?string $order = null): string
    {
        return $order && in_array(strtoupper($order), ['DESC', 'ASC'], true) ? $order : '';
    }
}
