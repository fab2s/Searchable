<?php

/*
 * This file is part of fab2s/laravel-dt0.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/laravel-dt0
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\SearchableInterface;
use fab2s\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static Builder<static> search(string|array $search, ?string $order = 'DESC')
 */
class Model extends \Illuminate\Database\Eloquent\Model implements SearchableInterface
{
    use Searchable;
    protected $guarded     = [];
    protected $searchables = [
        'field1',
        'field2',
    ];

    public function getSearchableFieldDbSize(): int
    {
        return 500;
    }
}
