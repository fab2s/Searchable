<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\TermParser;

class TermParserTest extends TestCase
{
    /**
     * @dataProvider parseProvider
     *
     * @param string $input
     * @param string $mode
     * @param string $expected
     */
    public function testParse(string $input, string $mode, string $expected)
    {
        $this->assertSame($expected, TermParser::parse($input, $mode));
    }

    public function parseProvider(): array
    {
        return [
            'single_single' => [
                'term',
                TermParser::MODE_SINGLE,
                'term*',
            ],
            'single_multiple' => [
                'term',
                TermParser::MODE_MULTIPLE,
                'term*',
            ],
        ];
    }
}
