<?php

/*
 * This file is part of fab2s/laravel-dt0.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/laravel-dt0
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

class PhoneticModel extends Model
{
    protected $table = 'models';

    public function getSearchablePhonetic(): bool
    {
        return true;
    }
}
