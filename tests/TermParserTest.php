<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\Phonetic\Phonetic;
use fab2s\Searchable\TermParser;
use PHPUnit\Framework\Attributes\DataProvider;

class TermParserTest extends TestCase
{
    #[DataProvider('parseProvider')]
    public function test_parse(string $input, string $expected): void
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
            'single_multiple' => [
                'term',
                'term*',
            ],
        ];
    }

    #[DataProvider('parsePgsqlProvider')]
    public function test_parse_pgsql(string $input, string $expected): void
    {
        $this->assertSame($expected, TermParser::parse($input, 'pgsql'));
    }

    public static function parsePgsqlProvider(): array
    {
        return [
            'single_term' => [
                'term',
                'term:*',
            ],
            'multiple_terms' => [
                'term1 term2',
                'term1:* & term2:*',
            ],
            'trims_and_filters' => [
                '  hello   world  ',
                'hello:* & world:*',
            ],
        ];
    }

    #[DataProvider('parseArrayProvider')]
    public function test_parse_array(array $input, string $driver, string $expected): void
    {
        $this->assertSame($expected, TermParser::parse($input, $driver));
    }

    /** @return array<string, array{array<int,string>, string, string}> */
    public static function parseArrayProvider(): array
    {
        return [
            'mysql_array' => [
                ['hello', 'world'],
                'mysql',
                'hello* world*',
            ],
            'pgsql_array' => [
                ['hello', 'world'],
                'pgsql',
                'hello:* & world:*',
            ],
        ];
    }

    #[DataProvider('parseEmptyProvider')]
    public function test_parse_empty(string|array $input, string $driver): void
    {
        $this->assertSame('', TermParser::parse($input, $driver));
    }

    /** @return array<string, array{string|array<int,string>, string}> */
    public static function parseEmptyProvider(): array
    {
        return [
            'mysql_empty_string'   => ['', 'mysql'],
            'pgsql_empty_string'   => ['', 'pgsql'],
            'mysql_whitespace'     => ['   ', 'mysql'],
            'pgsql_whitespace'     => ['   ', 'pgsql'],
            'mysql_only_operators' => ['+- ~*"@', 'mysql'],
            'pgsql_only_operators' => ['+- ~*"@', 'pgsql'],
            'mysql_empty_array'    => [[], 'mysql'],
            'pgsql_empty_array'    => [[], 'pgsql'],
        ];
    }

    #[DataProvider('filterOperatorsProvider')]
    public function test_filter_strips_operators(string $input, string $expected): void
    {
        $this->assertSame($expected, TermParser::filter($input));
    }

    /** @return array<string, array{string, string}> */
    public static function filterOperatorsProvider(): array
    {
        return [
            'ampersand_pipe'  => ['foo & bar | baz&qux', 'foo bar baz qux'],
            'plus_minus'      => ['+foo -bar', 'foo bar'],
            'tilde'           => ['~foo', 'foo'],
            'double_quotes'   => ['"foo bar"', 'foo bar'],
            'at_sign'         => ['foo @3 bar', 'foo 3 bar'],
            'parentheses'     => ['(foo) (bar)', 'foo bar'],
            'angle_brackets'  => ['>foo <bar', 'foo bar'],
            'asterisk'        => ['foo* bar*', 'foo bar'],
            'punctuation_mix' => ['hello, world! how? yes.', 'hello world how yes'],
        ];
    }

    #[DataProvider('phoneticizeProvider')]
    public function test_phoneticize(string $input, string $expected): void
    {
        $this->assertSame($expected, TermParser::phoneticize($input, metaphone(...)));
    }

    public static function phoneticizeProvider(): array
    {
        return [
            'single_word' => [
                'john',
                'john jn',
            ],
            'multiple_words' => [
                'john smith',
                'john smith jn sm0',
            ],
            'phonetic_match' => [
                'jon',
                'jon jn',
            ],
            'numbers_only' => [
                '123',
                '123',
            ],
            'empty_string' => [
                '',
                '',
            ],
        ];
    }

    public function test_parse_mysql_phonetic(): void
    {
        $this->assertSame('john* jn*', TermParser::parse('john', 'mysql', true));
    }

    public function test_parse_mysql_phonetic_multiple(): void
    {
        $this->assertSame('john* smith* jn* sm0*', TermParser::parse('john smith', 'mysql', true));
    }

    public function test_parse_pgsql_phonetic(): void
    {
        $this->assertSame('jon:* & jn:*', TermParser::parse('jon', 'pgsql', true));
    }

    public function test_parse_pgsql_phonetic_multiple(): void
    {
        $this->assertSame('jon:* & smith:* & jn:* & sm0:*', TermParser::parse('jon smith', 'pgsql', true));
    }

    public function test_phoneticize_custom_encoder(): void
    {
        $this->assertSame('jean jan', TermParser::phoneticize('jean', Phonetic::encode(...)));
    }

    public function test_phoneticize_custom_encoder_multiple(): void
    {
        $this->assertSame('jean dupont jan dupon', TermParser::phoneticize('jean dupont', Phonetic::encode(...)));
    }

    public function test_parse_mysql_custom_phonetic(): void
    {
        $this->assertSame('jean* jan*', TermParser::parse('jean', 'mysql', true, Phonetic::encode(...)));
    }

    public function test_parse_pgsql_custom_phonetic(): void
    {
        $this->assertSame('jean:* & jan:*', TermParser::parse('jean', 'pgsql', true, Phonetic::encode(...)));
    }

    #[DataProvider('prepareSearchableProvider')]
    public function test_prepare_searchable(array $input, string $expected): void
    {
        $this->assertSame($expected, TermParser::prepareSearchable(...$input));
    }

    /** @return array<string, array{array<int,string|array<int,string>>, string}> */
    public static function prepareSearchableProvider(): array
    {
        return [
            'single_string' => [
                ['Hello World'],
                'hello world',
            ],
            'multiple_strings' => [
                ['Hello', 'World'],
                'hello world',
            ],
            'array_input' => [
                [['Hello', 'World']],
                'hello world',
            ],
            'mixed_inputs' => [
                [['Hello', 'World'], 'Extra'],
                'hello world extra',
            ],
            'strips_operators' => [
                ['Hello +World "Foo"'],
                'hello world foo',
            ],
        ];
    }
}
