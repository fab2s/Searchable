<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Traits;

use fab2s\Searchable\SearchQuery;
use fab2s\Searchable\TermParser;
use Illuminate\Database\Eloquent\Model;

trait Searchable
{
    /**
     * @return string
     */
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

    /**
     * @return int
     */
    public function getSearchableFieldDbSize(): int
    {
        return 255;
    }

    /**
     * @param string $additional for case where this method is overridden in users
     *
     * @return string
     */
    public function getSearchableContent(string $additional = ''): string
    {
        return TermParser::prepareSearchable(array_map(function ($field) {
            return $this->$field;
        }, $this->getSearchables()), $additional);
    }

    public static function bootSearchable():void
    {
        static::retrieved(function (Model $model) {
            /** @var Searchable $model */
            $model->makeHidden($model->getSearchableField());
        });

        static::saving(function (Model $model) {
            /** @var Searchable $model */
            $model->{$model->getSearchableField()} = $model->getSearchableContent();
        });
    }

    public function getSearchables(): array
    {
        return $this->searchables ?? [];
    }
}
