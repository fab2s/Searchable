<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\Traits\Searchable;

class Model extends \Illuminate\Database\Eloquent\Model
{
    use Searchable;
    protected $guarded     = [];
    protected $searchables = [
        'field1',
        'field2',
    ];

    /**
     * @return int
     */
    public function getSearchableFieldDbSize(): int
    {
        return 500;
    }
}
