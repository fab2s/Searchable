<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\TermParser;
use PHPUnit\Framework\Attributes\DataProvider;

class TermParserTest extends TestCase
{
    #[DataProvider('parseProvider')]
    public function test_parse(string $input, string $expected)
    {
        $this->assertSame($expected, TermParser::parse($input));
    }

    public static function parseProvider(): array
    {
        return [
            'single_single' => [
                'term',
                'term*',
            ],
            'multiple' => [
                'Term1 teRm2',
                'term1* term2*',
            ],
            'ws' => [
                "Term1    \n\n    teRm2",
                'term1* term2*',
            ],
        ];
    }
}
