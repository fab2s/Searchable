# Searchable
[![CI](https://github.com/fab2s/Searchable/actions/workflows/ci.yml/badge.svg)](https://github.com/fab2s/Searchable/actions/workflows/ci.yml) [![QA](https://github.com/fab2s/Searchable/actions/workflows/qa.yml/badge.svg)](https://github.com/fab2s/Searchable/actions/workflows/qa.yml) [![Latest Stable Version](http://poser.pugx.org/fab2s/Searchable/v)](https://packagist.org/packages/fab2s/Searchable) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com) [![License](http://poser.pugx.org/fab2s/Searchable/license)](https://packagist.org/packages/fab2s/Searchable) 

Searchable models for [**Laravel**](https://laravel.com/) (The Awesome) based on Mysql FullText indexes.

This package does not try to be smart, just KISS. It will allow you to make any filed of your model searchable by concatenating them into a new column indexed with a mysql FullText index.

> It requires mysql / mariadb and Laravel 9.x

## Installation

`Searchable` can be installed using composer:

```shell
composer require "fab2s/searchable"
```

## Usage

To start using `Searchable` on a specific `model`, just `use`  the `Searchable` trait and setup `$searchables`:

```php
class MyModel extends Model
{
    use Searchable;
    
     /**
     * @var string[]
     */
    protected $searchables = [
        'field1',
        'field2',
        // ...
        'fieldN',
    ];
}
```

By default, `field1` to `fieldN` will be concatenated and stored into the default `SearchQuery::SEARCHABLE_FIELD` field added to the model by the `Enable` command.

By default, this searchable field will be of type `VARCHAR(255)`, but you can customize it at will with any type and length supporting a FullText index by just overriding the `Searchable` trait method in your model:

````php
    /**
     * @return string
     */
    public function getSearchableField(): string
    {
        return SearchQuery::SEARCHABLE_FIELD; // searchable
    }

    /**
     * @return string any migration method such as string, text etc ...
     */
    public function getSearchableFieldDbType(): string
    {
        return 'string';
    }

    /**
     * @return int
     */
    public function getSearchableFieldDbSize(): int
    {
        return 255;
    }

````

You can customise concatenation as well overriding:

````php
    /**
     * @param string $additional for case where this method is overridden in users
     *
     * @return string
     */
    public function getSearchableContent(string $additional = ''): string
    {
        return TermParser::prepareSearchable(array_map(function ($field) {
            return $this->$field;
        }, $this->getSearchables()), $additional);
    }
````

The `$additional` parameter can be used to preprocess model data if needed, can be handy to encrypt/decrypt or anonymize for example:

````php
    /**
     * @return string
     */
    public function getSearchableContent(): string
    {
        $additional = [
            $this->decrypt('additional_field1'),
            // assuming E.164 format:
            // +33601010101 would be indexed by its 0601010 prefix only,
            // allowing for decent autocomplete narrowing and privacy
            0 . substr((string) $this->decrypt('phone'), 3, 6), 
        ];

        return $this->getSearchableContentTrait(implode(' ', $additional));
    }
````

Once you have configured your model(s), you can use the `Enable` command to add the `searchable` field to your models and / or index them:

````shell
$ php artisan searchable:enable --help
Description:
  Enable searchable for your models

Usage:
  searchable:enable [options]

Options:
      --root[=ROOT]     The place where to start looking for models, defaults to Laravel's app/Models
      --index           To also index / re index
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
      --env[=ENV]       The environment the command should run under
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
````

## Stopwords

`Searchable` comes with an English and French stop words files which you can use to reduce FullText indexing by ignoring words listed in [these files](./src/stopwords).

The `StopWords` command can be used to populate a `stopwords` table with these words:

````shell
php artisan searchable:stopwords
````

The db server configuration must be configured as demonstrated in [innodb_full_text.cnf](./src/innodb_full_text.cnf) for these words to effectively be excluded from indexing.

## Requirements

`Searchable` is tested against php 8.1, 8.2, 8.3 and 8.4

## Contributing

Contributions are welcome, do not hesitate to open issues and submit pull requests.

## License

`Searchable` is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
