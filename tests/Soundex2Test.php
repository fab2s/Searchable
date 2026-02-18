<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\Phonetic\Soundex2;
use PHPUnit\Framework\Attributes\DataProvider;

class Soundex2Test extends TestCase
{
    #[DataProvider('encodeProvider')]
    public function test_encode(string $input, string $expected): void
    {
        $this->assertSame($expected, Soundex2::encode($input));
    }

    public static function encodeProvider(): array
    {
        return [
            ['Asamian', 'AZMN'],
            ['Knight', 'NG'],
            ['MacKenzie', 'MKNZ'],
            ['Pfeifer', 'FR'],
            ['Philippe', 'FLP'],
            ['Schindler', 'SNDL'],
            ['Chateau', 'CHT'],
            ['Habitat', 'HBT'],
            ['Téhéran', 'TRN'],
            ['Essayer', 'ESYR'],
            ['Crayon', 'CRYN'],
            ['Plyne', 'PLN'],
            ['Barad', 'BR'],
            ['Martin', 'MRTN'],
            ['Bernard', 'BRNR'],
            ['Faure', 'FR'],
            ['Perez', 'PRZ'],
            ['Gros', 'GR'],
            ['Chapuis', 'CHP'],
            ['Boyer', 'BYR'],
            ['Gauthier', 'KTR'],
            ['Rey', 'RY'],
            ['Barthélémy', 'BRTL'],
            ['Henry', 'HNR'],
            ['Moulin', 'MLN'],
            ['Rousseau', 'RS'],
        ];
    }

    public function test_phonetic_equivalence(): void
    {
        $this->assertSame(Soundex2::encode('Faure'), Soundex2::encode('Phaure'));
    }

    public function test_empty_string(): void
    {
        $this->assertSame('', Soundex2::encode(''));
    }

    public function test_whitespace_only(): void
    {
        $this->assertSame('', Soundex2::encode('   '));
    }

    public function test_empty_after_cleanup(): void
    {
        $this->assertSame('', Soundex2::encode('a'));
    }
}
