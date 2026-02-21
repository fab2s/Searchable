<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

class ModelTest extends TestCase
{
    public function test_get_searchables(): void
    {
        $this->assertSame(['field1', 'field2'], (new Model)->getSearchables());
    }

    public function test_get_searchable_content(): void
    {
        $this->assertSame('value1 value2', (new Model)->fill([
            'field1' => 'value1',
            'field2' => 'value2',
            'field3' => 'value3',
        ])->getSearchableContent());
    }

    public function test_get_searchable_content_with_additional(): void
    {
        $this->assertSame('value1 value2 extra', (new Model)->fill([
            'field1' => 'value1',
            'field2' => 'value2',
        ])->getSearchableContent('Extra'));
    }

    public function test_get_searchable_field_db_size(): void
    {
        $this->assertSame(500, (new Model)->getSearchableFieldDbSize());
    }

    public function test_get_searchable_field_db_type(): void
    {
        $this->assertSame('string', (new Model)->getSearchableFieldDbType());
    }

    public function test_get_searchable_field(): void
    {
        $this->assertSame('searchable', (new Model)->getSearchableField());
    }

    public function test_get_searchable_ts_config(): void
    {
        $this->assertSame('english', (new Model)->getSearchableTsConfig());
    }

    public function test_get_searchable_phonetic_default(): void
    {
        $this->assertFalse((new Model)->getSearchablePhonetic());
    }

    public function test_get_searchable_phonetic_enabled(): void
    {
        $this->assertTrue((new PhoneticModel)->getSearchablePhonetic());
    }

    public function test_get_searchable_content_phonetic(): void
    {
        $content = (new PhoneticModel)->fill([
            'field1' => 'John',
            'field2' => 'Smith',
        ])->getSearchableContent();

        $this->assertSame('john smith jn sm0', $content);
    }

    public function test_get_searchable_content_phonetic_with_additional(): void
    {
        $content = (new PhoneticModel)->fill([
            'field1' => 'John',
            'field2' => 'Smith',
        ])->getSearchableContent('Extra');

        $this->assertSame('john smith extra jn sm0 ekstr', $content);
    }

    public function test_get_searchable_content_custom_phonetic(): void
    {
        $content = (new PhoneticFrModel)->fill([
            'field1' => 'Jean',
            'field2' => 'Dupont',
        ])->getSearchableContent();

        $this->assertSame('jean dupont jan dupon', $content);
    }

    public function test_boot_searchable(): void
    {
        $this->expectNotToPerformAssertions();
        Model::bootSearchable();
    }

    public function test_get_searchable_field_db_size_default(): void
    {
        $this->assertSame(500, (new DefaultSizeModel)->getSearchableFieldDbSize());
    }
}
