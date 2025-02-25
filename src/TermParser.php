<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use fab2s\Strings\Strings;
use fab2s\Utf8\Utf8;

class TermParser
{
    /**
     * @param string|array<int,string> $search
     */
    public static function parse(string|array $search): string
    {
        return implode(' ', array_map(function ($value) {
            return $value ? $value . '*' : '';
        }, explode(' ', static::filter($search))));
    }

    /**
     * @param string|array<int, string> $search
     */
    public static function filter(string|array $search): string
    {
        if (is_array($search)) {
            $search = str_replace('Array', '', implode(' ', array_filter($search)));
        }

        $search = trim(preg_replace([
            // drop operator (+, -, > <, ( ), ~, *, ", @distance)
            // and some punctuation
            '`[+\-><\(\)~*\"@,.:;?!]+`',
            // '`[+\-><\(\)~*\",.:;?!]+`',
        ], ' ', Strings::singleLineIze(Strings::normalizeText($search))));

        return Utf8::strtolower(preg_replace('`\s{2,}`', ' ', $search));
    }

    public static function prepareSearchable(string|array ...$input): string
    {
        $result = [];
        foreach ($input as $value) {
            $result = array_merge($result, (array) $value);
        }

        return static::filter($result);
    }
}
