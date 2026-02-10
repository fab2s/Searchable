# Searchable

[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat)](https://phpstan.org/)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

Fulltext search for [Laravel](https://laravel.com/) Eloquent models. Supports **MySQL** and **PostgreSQL**.

This package keeps things simple: it concatenates model fields into a single indexed column and uses native fulltext capabilities (`MATCH...AGAINST` on MySQL, `tsvector/tsquery` on PostgreSQL) for fast prefix-based search, ideal for autocomplete.

## Requirements

- PHP 8.1+
- Laravel 10.x / 11.x / 12.x
- MySQL / MariaDB or PostgreSQL

## Installation

```shell
composer require fab2s/searchable
```

The service provider is auto-discovered.

## Quick start

Implement `SearchableInterface` on your model, use the `Searchable` trait, and list the fields to index:

```php
use fab2s\Searchable\SearchableInterface;
use fab2s\Searchable\Traits\Searchable;

class Contact extends Model implements SearchableInterface
{
    use Searchable;

    protected $searchables = [
        'first_name',
        'last_name',
        'email',
    ];
}
```

Then run the artisan command to add the column and fulltext index:

```shell
php artisan searchable:enable
```

That's it. The `searchable` column is automatically populated on every save.

## Searching

Use `SearchQuery` to add a fulltext clause to any Eloquent query:

```php
use fab2s\Searchable\SearchQuery;

$search = new SearchQuery;
$query  = Contact::query();

$search->addMatch($query, $request->input('q'));

$results = $query->get();
```

The driver is detected automatically from the query's connection. Results are ordered by relevance by default (pass `null` as the first constructor argument to disable).

## Configuration

### Column type and size

Override trait methods to customize the searchable column:

```php
public function getSearchableFieldDbType(): string
{
    return 'text'; // default: 'string'
}

public function getSearchableFieldDbSize(): int
{
    return 1000; // default: 255
}
```

### Custom content

Override `getSearchableContent()` to control what gets indexed. The `$additional` parameter lets you inject extra data (decrypted fields, computed values, etc.):

```php
public function getSearchableContent(string $additional = ''): string
{
    $extra = implode(' ', [
        $this->decrypt('phone'),
        $this->some_computed_value,
    ]);

    return parent::getSearchableContent($extra);
}
```

### PostgreSQL text search configuration

By default, PostgreSQL uses the `english` text search configuration. Override to change it:

```php
public function getSearchableTsConfig(): string
{
    return 'french';
}
```

Pass the same value to `SearchQuery` when querying:

```php
$search = new SearchQuery('DESC', 'searchable', 'french');
```

### Phonetic matching

Enable phonetic matching to find results despite spelling variations (eg. "jon" matches "john", "smyth" matches "smith"). This uses PHP's `metaphone()` to append phonetic codes to the same searchable field — no extra column or extension needed.

```php
public function getSearchablePhonetic(): bool
{
    return true;
}
```

Then enable it on the search side too:

```php
$search = new SearchQuery('DESC', 'searchable', 'english', phonetic: true);
```

Stored content becomes `john smith jn sm0`, and a search for `jon` produces the term `jn` which matches.

## The Enable command

```shell
# Add searchable column + index to all models using the Searchable trait
php artisan searchable:enable

# Target a specific model
php artisan searchable:enable --model=App/Models/Contact

# Also (re)index existing records
php artisan searchable:enable --model=App/Models/Contact --index

# Scan a custom directory for models
php artisan searchable:enable --root=app/Domain/Models
```

The command detects the database driver and creates the appropriate index:
- **MySQL**: `ALTER TABLE ... ADD FULLTEXT`
- **PostgreSQL**: `CREATE INDEX ... USING GIN(to_tsvector(...))`

## Contributing

Contributions are welcome. Feel free to open issues and submit pull requests.

## License

`Searchable` is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
