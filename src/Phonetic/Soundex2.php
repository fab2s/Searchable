<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Phonetic;

use Normalizer;

/**
 * French Soundex2 phonetic algorithm.
 *
 * Optimized port from Talisman (MIT) - https://github.com/Yomguithereal/talisman
 *
 * @see http://www-info.univ-lemans.fr/~carlier/recherche/soundex.html
 * @see http://sqlpro.developpez.com/cours/soundex/
 */
class Soundex2 implements PhoneticInterface
{
    /**
     * Letter groups + non-initial vowel → A.
     *
     * @var list<string>
     */
    protected const FIRST_PATTERNS = ['~GU([IE])~', '~G([AO])~', '~GU~', '~C([AOU])~', '~(?:Q|CC|CK)~', '~(?!^)[AEIOU]~'];

    /** @var list<string> */
    protected const FIRST_REPLACEMENTS = ['K$1', 'K$1', 'K', 'K$1', 'K', 'A'];

    /**
     * Remove H (not after C/S), Y (not after A), trailing A/D/T/S,
     * non-initial A placeholders, and squeeze consecutive duplicates.
     *
     * @var list<string>
     */
    protected const END_PATTERNS = ['~([^CS])H~', '~([^A])Y~', '~[ADTS]$~', '~(?!^)A~', '~(.)\1+~'];

    /** @var list<string> */
    protected const END_REPLACEMENTS = ['$1', '$1', '', '', '$1'];
    protected const PREFIXES         = [
        'MAC' => 'MCC',
        'SCH' => 'SSS',
        'ASA' => 'AZA',
        'KN'  => 'NN',
        'PH'  => 'FF',
        'PF'  => 'FF',
    ];

    public static function encode(string $name): string
    {
        $code = mb_strtoupper(static::deburr(trim($name)));
        $code = (string) preg_replace('~[^A-Z]+~', '', $code);

        if ($code === '') {
            return '';
        }

        // Letter groups + non-initial vowels → A
        $code = (string) preg_replace(static::FIRST_PATTERNS, static::FIRST_REPLACEMENTS, $code);

        // Replacing prefixes
        foreach (static::PREFIXES as $prefix => $replacement) {
            if (str_starts_with($code, $prefix)) {
                $code = $replacement . substr($code, strlen($prefix));
                break;
            }
        }

        // Cleanup + remove A placeholders + squeeze, then truncate to 4
        $code = (string) preg_replace(static::END_PATTERNS, static::END_REPLACEMENTS, $code);

        if ($code === '') {
            return '';
        }

        return substr($code, 0, 4);
    }

    /**
     * Strip diacritical marks and decompose ligatures.
     */
    protected static function deburr(string $string): string
    {
        $string = str_replace(
            ['Œ', 'œ', 'Æ', 'æ'],
            ['OE', 'oe', 'AE', 'ae'],
            $string,
        );

        $normalized = Normalizer::normalize($string, Normalizer::FORM_D);

        return (string) preg_replace('~\p{Mn}~u', '', (string) $normalized);
    }
}
