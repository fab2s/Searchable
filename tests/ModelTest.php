<?php

/*
 * This file is part of Searchable
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

class ModelTest extends TestCase
{
    public function testGetSearchables()
    {
        $this->assertSame(['field1', 'field2'], (new Model)->getSearchables());
    }

    public function testGetSearchableContent()
    {
        $this->assertSame('value1 value2', (new Model)->fill([
            'field1' => 'value1',
            'field2' => 'value2',
            'field3' => 'value3',
        ])->getSearchableContent());
    }

    public function testGetSearchableFieldDbSize()
    {
        $this->assertSame(500, (new Model)->getSearchableFieldDbSize());
    }

    public function testGetSearchableFieldDbType()
    {
        $this->assertSame('string', (new Model)->getSearchableFieldDbType());
    }

    public function testGetSearchableField()
    {
        $this->assertSame('searchable', (new Model)->getSearchableField());
    }

    public function testBootSearchable()
    {
        Model::bootSearchable();
        $this->assertTrue(true);
    }
}
