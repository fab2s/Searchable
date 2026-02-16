<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\Phonetic\Phonetic;

class PhoneticModel extends Model
{
    protected $table = 'models';

    public function getSearchablePhonetic(): bool
    {
        return true;
    }
}

class PhoneticFrModel extends Model
{
    protected $table                              = 'models';
    protected bool $searchablePhonetic            = true;
    protected string $searchablePhoneticAlgorithm = Phonetic::class;
}
