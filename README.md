# Searchable

[![CI](https://github.com/fab2s/Searchable/actions/workflows/ci.yml/badge.svg)](https://github.com/fab2s/Searchable/actions/workflows/ci.yml)
[![QA](https://github.com/fab2s/Searchable/actions/workflows/qa.yml/badge.svg)](https://github.com/fab2s/Searchable/actions/workflows/qa.yml)
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

> **Choosing fields wisely:** The quality of matching depends directly on which fields you index. This package is designed for fast, simple autocomplete — not complex full-text search. Keep `$searchables` focused on the few fields users actually type into a search box (names, titles, emails). Adding large or numerous fields dilutes relevance and increases storage. If you need weighted fields, facets, or advanced ranking, consider a dedicated search engine instead.

## Searching

The trait provides a `search` scope that handles everything automatically:

```php
$results = Contact::search($request->input('q'))->get();
```

It composes with other query builder methods:

```php
$results = Contact::search('john')
    ->where('active', true)
    ->limit(10)
    ->get();
```

Results are ordered by relevance (DESC) by default. Pass `null` to disable:

```php
$results = Contact::search('john', null)->latest()->get();
```

The driver is detected automatically from the query's connection. The scope picks up the model's `tsConfig` and `phonetic` settings.

> For IDE autocompletion, add a `@method` annotation to your model:
> ```php
> /**
>  * @method static Builder<static> search(string|array $search, ?string $order = 'DESC')
>  */
> class Contact extends Model implements SearchableInterface
> ```

### Advanced usage with SearchQuery

For more control (table aliases in joins, custom field name), use `SearchQuery` directly:

```php
use fab2s\Searchable\SearchQuery;

$search = new SearchQuery('DESC', 'searchable', 'english', phonetic: true);
$query  = Contact::query();

$search->addMatch($query, $request->input('q'), 'contacts');

$results = $query->get();
```

## Configuration

Every option can be set by declaring a property on your model. The trait picks them up automatically and falls back to sensible defaults when omitted:

| Property                | Type           | Default         | Description                              |
|-------------------------|----------------|-----------------|------------------------------------------|
| `$searchableField`      | `string`       | `'searchable'`  | Column name for the searchable content   |
| `$searchableFieldDbType`| `string`       | `'string'`      | Migration column type (`string`, `text`)  |
| `$searchableFieldDbSize`| `int`          | `500`           | Column size (applies to `string` type)   |
| `$searchables`          | `array<string>`| `[]`            | Model fields to index                    |
| `$searchableTsConfig`   | `string`       | `'english'`     | PostgreSQL text search configuration     |
| `$searchablePhonetic`   | `bool`         | `false`         | Enable phonetic matching                 |

```php
class Contact extends Model implements SearchableInterface
{
    use Searchable;

    protected array $searchables = ['first_name', 'last_name', 'email'];
    protected string $searchableTsConfig = 'french';
    protected bool $searchablePhonetic = true;
    protected int $searchableFieldDbSize = 1000;
}
```

Each property has a corresponding getter method (`getSearchableField()`, `getSearchableFieldDbType()`, etc.) defined in `SearchableInterface`. You can override those methods instead if you need computed values.

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

By default, PostgreSQL uses the `english` text search configuration. Set `$searchableTsConfig` to change it:

```php
protected string $searchableTsConfig = 'french';
```

The `search` scope picks this up automatically. When using `SearchQuery` directly, pass the same value:

```php
$search = new SearchQuery('DESC', 'searchable', 'french');
```

### Phonetic matching

Enable phonetic matching to find results despite spelling variations (eg. "jon" matches "john", "smyth" matches "smith"). This uses PHP's `metaphone()` to append phonetic codes to the same searchable field — no extra column or extension needed.

```php
protected bool $searchablePhonetic = true;
```

That's all — both storage and the `search` scope handle it automatically. Stored content becomes `john smith jn sm0`, and a search for `jon` produces the term `jn` which matches.

When using `SearchQuery` directly, pass the phonetic flag:

```php
$search = new SearchQuery('DESC', 'searchable', 'english', phonetic: true);
```

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

```shell
# fix code style
composer fix

# run tests
composer test

# run tests with coverage
composer cov

# static analysis (src, level 9)
composer stan

# static analysis (tests, level 5)
composer stan-tests
```

## License

`Searchable` is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
