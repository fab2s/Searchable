<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Traits;

use Closure;
use fab2s\Searchable\Phonetic\PhoneticInterface;
use fab2s\Searchable\SearchQuery;
use fab2s\Searchable\TermParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 *
 * @property string                          $searchableField
 * @property string                          $searchableFieldDbType
 * @property int                             $searchableFieldDbSize
 * @property array<string>                   $searchables
 * @property string                          $searchableTsConfig
 * @property bool                            $searchablePhonetic
 * @property class-string<PhoneticInterface> $searchablePhoneticAlgorithm
 */
trait Searchable
{
    public function getSearchableField(): string
    {
        return $this->searchableField ?? SearchQuery::SEARCHABLE_FIELD;
    }

    /**
     * @return string any migration method such as string, text etc ...
     */
    public function getSearchableFieldDbType(): string
    {
        return $this->searchableFieldDbType ?? 'string';
    }

    public function getSearchableFieldDbSize(): int
    {
        return $this->searchableFieldDbSize ?? 500;
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
            $content = TermParser::phoneticize($content, $this->getSearchablePhoneticClosure());
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
        return $this->searchableTsConfig ?? 'english';
    }

    public function getSearchablePhonetic(): bool
    {
        return $this->searchablePhonetic ?? false;
    }

    /** @return Closure(string): string */
    public function getSearchablePhoneticClosure(): Closure
    {
        return isset($this->searchablePhoneticAlgorithm)
            ? $this->searchablePhoneticAlgorithm::encode(...)
            : metaphone(...);
    }

    /** @param Builder<Model> $query */
    public function scopeSearch(Builder $query, string|array $search, ?string $order = 'DESC'): void
    {
        (new SearchQuery($order, $this->getSearchableField(), $this->getSearchableTsConfig(), $this->getSearchablePhonetic(), $this->getSearchablePhoneticClosure()))
            ->addMatch($query, $search)
        ;
    }
}
