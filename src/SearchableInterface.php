<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable;

use Closure;

interface SearchableInterface
{
    public function getSearchableField(): string;

    /**
     * @return string any migration method such as string, text etc ...
     */
    public function getSearchableFieldDbType(): string;

    public function getSearchableFieldDbSize(): int;

    public function getSearchableContent(string $additional = ''): string;

    /** @return array<int,string> */
    public function getSearchables(): array;

    public function getSearchableTsConfig(): string;

    public function getSearchablePhonetic(): bool;

    public function getSearchablePhoneticClosure(): Closure;
}
