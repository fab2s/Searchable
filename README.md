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
| `$searchablePhoneticAlgorithm` | `class-string<PhoneticInterface>` | — (metaphone) | Custom phonetic encoder class |

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

### Custom phonetic algorithm

The default `metaphone()` works well for English. For other languages, set `$searchablePhoneticAlgorithm` to any class implementing `PhoneticInterface`:

```php
use fab2s\Searchable\Phonetic\PhoneticInterface;

class MyEncoder implements PhoneticInterface
{
    public static function encode(string $word): string
    {
        // your encoding logic
    }
}
```

Then reference it on your model:

```php
use fab2s\Searchable\Phonetic\Phonetic;

class Contact extends Model implements SearchableInterface
{
    use Searchable;

    protected array $searchables = ['first_name', 'last_name'];
    protected bool $searchablePhonetic = true;
    protected string $searchablePhoneticAlgorithm = Phonetic::class;
}
```

The trait resolves the class to a closure internally — no method override needed.

When using `SearchQuery` directly, pass the encoder as a closure:

```php
$search = new SearchQuery('DESC', 'searchable', 'french', phonetic: true, phoneticAlgorithm: Phonetic::encode(...));
```

### Built-in French encoders

Two French phonetic algorithms are included, ported from [Talisman](https://github.com/Yomguithereal/talisman) (MIT):

| Class | Algorithm | Description |
|-------|-----------|-------------|
| `Phonetic` | [Phonetic Français](http://www.roudoudou.com/phonetic.php) | Comprehensive French phonetic algorithm by Edouard Berge. Handles ligatures, silent letters, nasal vowels, and many French-specific spelling rules. |
| `Soundex2` | [Soundex2](http://sqlpro.developpez.com/cours/soundex/) | French adaptation of Soundex. Simpler and faster than `Phonetic`, produces 4-character codes. |

Both implement `PhoneticInterface` and handle Unicode normalization (accents, ligatures like œ and æ) internally.

```php
use fab2s\Searchable\Phonetic\Phonetic;
use fab2s\Searchable\Phonetic\Soundex2;

Phonetic::encode('jean');   // 'JAN'
Soundex2::encode('dupont'); // 'DIPN'
```

### Phonetic encoder benchmarks

Measured on a set of 520 French words, 1000 iterations each (PHP 8.4):

| Encoder    | Per word | Throughput |
|------------|----------|------------|
| metaphone  | ~2 µs   | ~500k/s    |
| Soundex2   | ~35 µs  | ~28k/s     |
| Phonetic   | ~51 µs  | ~20k/s     |

PHP's native `metaphone()` is a C extension and unsurprisingly the fastest. Both French encoders are pure PHP with extensive regex-based rule sets, yet fast enough for typical use — encoding 1000 words takes under 50ms.

## Automatic setup after migrations

The package listens to Laravel's `MigrationsEnded` event and automatically runs `searchable:enable` after every successful `up` migration. This means:

- After `php artisan migrate`, the searchable column and fulltext index are added to any new Searchable model.
- After `php artisan migrate:fresh`, they are recreated along with the rest of your schema.
- Rollbacks (`down`) and pretended migrations (`--pretend`) are ignored.

This is fully automatic — no configuration needed. If you need to re-index existing records, run the command manually with `--index`.

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

### Adding Searchable to an existing model

You can add the Searchable feature to a model with pre-existing data at any time. After implementing `SearchableInterface` and using the `Searchable` trait, run the enable command with `--index` to set up the column, create the fulltext index, and populate it for all existing records:

```shell
php artisan searchable:enable --model=App/Models/Contact --index
```

You can also run it without `--model` to process all Searchable models at once.

### When to re-index

The searchable column is automatically kept in sync on every Eloquent `save`. Manual re-indexing is only needed when:

- **Adding Searchable to a model with existing data** — existing rows have no searchable content yet.
- **Changing `$searchables`** — after adding or removing fields from the index, existing rows still contain the old content.
- **Mass imports that bypass Eloquent** — raw SQL inserts, `DB::insert()`, or bulk imports that skip model events won't populate the searchable column.

In all these cases, run:

```shell
# re-index a specific model
php artisan searchable:enable --model=App/Models/Contact --index

# or re-index all Searchable models
php artisan searchable:enable --index
```

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
