<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

class ModelTest extends TestCase
{
    public function test_get_searchables()
    {
        $this->assertSame(['field1', 'field2'], (new Model)->getSearchables());
    }

    public function test_get_searchable_content()
    {
        $this->assertSame('value1 value2', (new Model)->fill([
            'field1' => 'value1',
            'field2' => 'value2',
            'field3' => 'value3',
        ])->getSearchableContent());
    }

    public function test_get_searchable_field_db_size()
    {
        $this->assertSame(500, (new Model)->getSearchableFieldDbSize());
    }

    public function test_get_searchable_field_db_type()
    {
        $this->assertSame('string', (new Model)->getSearchableFieldDbType());
    }

    public function test_get_searchable_field()
    {
        $this->assertSame('searchable', (new Model)->getSearchableField());
    }

    public function test_boot_searchable()
    {
        Model::bootSearchable();
        $this->assertTrue(true);
    }
}
