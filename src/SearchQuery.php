<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use Illuminate\Database\Eloquent\Builder;

class SearchQuery
{
    const SEARCHABLE_FIELD = 'searchable';

    /**
     * @var string
     */
    protected $mode = TermParser::MODE_SINGLE;

    /**
     * @var string|null
     */
    protected $order;

    /**
     * @var string
     */
    protected $searchableField = self::SEARCHABLE_FIELD;

    /**
     * Search constructor.
     *
     * @param string      $mode
     * @param string|null $order
     * @param string      $searchableField
     */
    public function __construct(string $mode = TermParser::MODE_SINGLE, ?string $order = 'DESC', string $searchableField = self::SEARCHABLE_FIELD)
    {
        $this->mode            = $mode;
        $this->order           = $this->getOrder($order);
        $this->searchableField = $searchableField;
    }

    /**
     * @param Builder     $query
     * @param string      $search
     * @param string|null $mode
     * @param string|null $order
     */
    public function addMatch(Builder $query, string $search, string $tableAlias = '', ?string $mode = null, ?string $order = null)
    {
        $terms = TermParser::parse($search, $mode);
        if (empty($terms)) {
            return;
        }

        $searchField  = ($tableAlias ? "$tableAlias." : '') . $this->searchableField;
        $query->whereRaw('MATCH (' . $searchField . ') AGAINST (? IN BOOLEAN MODE)', [$terms]);
        $order = $order ? $this->getOrder($order) : $this->order;

        if ($order) {
            $query->orderByRaw('(MATCH (' . $searchField . ') AGAINST (? IN BOOLEAN MODE)) ' . $order, [$terms]);
        }
    }

    /**
     * @param string|null $order
     *
     * @return string
     */
    public function getOrder(?string $order = null): string
    {
        return $order && in_array(strtoupper($order), ['DESC', 'ASC'], true) ? $order : '';
    }
}
