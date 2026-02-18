<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\SearchableInterface;
use fab2s\Searchable\Traits\Searchable;

class NoTimestampModel extends \Illuminate\Database\Eloquent\Model implements SearchableInterface
{
    use Searchable;
    protected $table       = 'no_timestamp_models';
    protected $guarded     = [];
    public $timestamps     = false;
    protected $searchables = [
        'field1',
    ];

    public function getSearchableFieldDbSize(): int
    {
        return 500;
    }
}
