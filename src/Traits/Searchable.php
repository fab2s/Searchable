<?php

/*
 * This file is part of fab2s/laravel-dt0.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/laravel-dt0
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Traits;

use fab2s\Searchable\SearchQuery;
use fab2s\Searchable\TermParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Searchable
{
    public function getSearchableField(): string
    {
        return SearchQuery::SEARCHABLE_FIELD;
    }

    /**
     * @return string any migration method such as string, text etc ...
     */
    public function getSearchableFieldDbType(): string
    {
        return 'string';
    }

    public function getSearchableFieldDbSize(): int
    {
        return 255;
    }

    /**
     * @param string $additional for case where this method is overridden in users
     */
    public function getSearchableContent(string $additional = ''): string
    {
        $content = TermParser::prepareSearchable(array_map(function ($field) {
            return $this->$field;
        }, $this->getSearchables()), $additional);

        if ($this->getSearchablePhonetic()) {
            $content = TermParser::phoneticize($content);
        }

        return $content;
    }

    public function initializeSearchable(): void
    {
        $this->makeHidden($this->getSearchableField());
    }

    public static function bootSearchable(): void
    {
        static::saving(function (Model $model) {
            /* @var Searchable $model */
            $model->{$model->getSearchableField()} = $model->getSearchableContent();
        });
    }

    public function getSearchables(): array
    {
        return $this->searchables ?? [];
    }

    public function getSearchableTsConfig(): string
    {
        return 'english';
    }

    public function getSearchablePhonetic(): bool
    {
        return false;
    }

    /** @param Builder<Model> $query */
    public function scopeSearch(Builder $query, string|array $search, ?string $order = 'DESC'): void
    {
        (new SearchQuery($order, $this->getSearchableField(), $this->getSearchableTsConfig(), $this->getSearchablePhonetic()))
            ->addMatch($query, $search)
        ;
    }
}
