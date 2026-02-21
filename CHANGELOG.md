# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-02-21

### Added

- Fulltext search for Eloquent models using native database capabilities (`MATCH...AGAINST` on MySQL/MariaDB, `tsvector/tsquery` on PostgreSQL)
- `Searchable` trait and `SearchableInterface` for easy model integration
- `SearchQuery` class for advanced usage (joins, table aliases, custom field names)
- `TermParser` for driver-aware term parsing and content preparation
- Phonetic matching support with pluggable algorithms via `PhoneticInterface` — also provides typo tolerance by matching phonetically similar inputs
- Built-in French phonetic encoders: `Phonetic` (Phonetic Francais) and `Soundex2` — optimized PHP ports from [Talisman](https://github.com/Yomguithereal/talisman)
- `searchable:enable` artisan command to add columns, fulltext indexes, and (re)index data with optimized batch processing
- Automatic setup after migrations via `MigrationsEnded` event listener
- Support for PHP 8.1 - 8.4
- Support for Laravel 10.x, 11.x, and 12.x
- Support for MySQL, MariaDB, and PostgreSQL
