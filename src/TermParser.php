<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use fab2s\Strings\Strings;
use fab2s\Utf8\Utf8;

class TermParser
{
    public static function parse(string $search): string
    {
        return implode(' ', array_map(function ($value) {
            return $value ? $value . '*' : '';
        }, explode(' ', static::filter($search))));
    }

    /**
     * @param string $search
     *
     * @return string
     */
    public static function filter(string $search): string
    {
        $search = trim(preg_replace([
            // drop operator (+, -, > <, ( ), ~, *, ", @distance)
            // and some punctuation
            '`[+\-><\(\)~*\"@,.:;?!]+`',
            //'`[+\-><\(\)~*\",.:;?!]+`',
        ], ' ', Strings::singleLineIze(Strings::normalizeText($search))));

        return preg_replace('`\s{2,}`', ' ', Utf8::strtolower(Strings::singleWsIze($search, true)));
    }

    /**
     * @param ...string[]|string $input
     *
     * @return string
     */
    public static function prepareSearchable(...$input): string
    {
        $input  = is_string($input) ? func_get_args() : $input;
        $result = [];
        foreach ($input as $value) {
            $result = array_merge($result, (array) $value);
        }

        return static::filter(implode(' ', array_filter(array_map('trim', $result))));
    }
}
