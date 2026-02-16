<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use Closure;
use fab2s\Strings\Strings;
use fab2s\Utf8\Utf8;

class TermParser
{
    /**
     * @param string|array<int,string> $search
     */
    public static function parse(string|array $search, string $driver = 'mysql', bool $phonetic = false, ?Closure $phoneticAlgorithm = null): string
    {
        $filtered = static::filter($search);

        if ($phonetic) {
            $filtered = static::phoneticize($filtered, $phoneticAlgorithm ?? metaphone(...));
        }

        if ($driver === 'pgsql') {
            return implode(' & ', array_filter(array_map(function ($value) {
                return $value !== '' ? $value . ':*' : '';
            }, explode(' ', $filtered))));
        }

        return implode(' ', array_map(function ($value) {
            return $value !== '' ? $value . '*' : '';
        }, explode(' ', $filtered)));
    }

    /**
     * @param string|array<int, string> $search
     */
    public static function filter(string|array $search): string
    {
        if (is_array($search)) {
            $search = str_replace('Array', '', implode(' ', array_filter($search)));
        }

        $search = trim((string) preg_replace([
            // drop operator (+, -, > <, ( ), ~, *, ", @distance)
            // and some punctuation
            '`[+\-><\(\)~*\"@,.:;?!&|]+`',
            // '`[+\-><\(\)~*\",.:;?!]+`',
        ], ' ', Strings::singleLineIze(Strings::normalizeText($search))));

        return (string) preg_replace('`\s{2,}`', ' ', Utf8::strtolower(Strings::singleWsIze($search, true)));
    }

    public static function phoneticize(string $filtered, Closure $encoder): string
    {
        $words = array_filter(explode(' ', $filtered), fn (string $word) => $word !== '');
        $codes = [];
        foreach ($words as $word) {
            $code = $encoder($word);
            if ($code !== '') {
                $codes[] = strtolower($code);
            }
        }

        return $codes ? $filtered . ' ' . implode(' ', $codes) : $filtered;
    }

    /**
     * @param string|array<string> ...$input
     */
    public static function prepareSearchable(string|array ...$input): string
    {
        $result = [];
        foreach ($input as $value) {
            $result = array_merge($result, (array) $value);
        }

        return static::filter($result);
    }
}
