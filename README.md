<div align="center">
    <a href="https://cycle-orm.dev" target="_blank">
        <picture>
            <source media="(prefers-color-scheme: dark)" srcset="https://github.com/cycle/.github/blob/main/logo/words-vector-dark.svg?raw=true">
            <img width="50%" align="center" src="https://github.com/cycle/.github/blob/main/logo/words-vector-light.svg?raw=true" alt="CycleORM Logo">
        </picture>
    </a>
</div>

<p align="center">Build a Cycle ORM schema from a chain of providers: files, cache, merged sources</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://cycle-orm.dev/docs)
[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/cycle)
[![Discord](https://img.shields.io/discord/538114875570913290?style=for-the-badge&logo=discord&labelColor=7289d9&logoColor=white&color=39456d)](https://discord.gg/spiralphp)

[![Psalm Level](https://shepherd.dev/github/cycle/schema-provider/level.svg)](https://shepherd.dev/github/cycle/schema-provider)
[![Type Coverage](https://shepherd.dev/github/cycle/schema-provider/coverage.svg)](https://shepherd.dev/github/cycle/schema-provider)
[![Codecov](https://img.shields.io/codecov/c/github/cycle/schema-provider/1.x?style=flat-square&logo=codecov)](https://codecov.io/gh/cycle/schema-provider)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fcycle%2Fschema-provider%2F1.x)](https://dashboard.stryker-mutator.io/reports/github.com/cycle/schema-provider/1.x)

</div>

<br />

[Cycle ORM](https://github.com/cycle/orm) uses an object implementing the `Cycle\ORM\SchemaInterface` interface as a schema.
This schema can be constructed from a PHP array with a specific structure. The package at hand offers a comprehensive
solution for building a schema from different sources. It includes a collection of providers that implements the
`Cycle\Schema\Provider\SchemaProviderInterface` interface. These providers are grouped in the
`Cycle\Schema\Provider\Support\SchemaProviderPipeline`.

This pipeline orchestrates the execution of providers in a predetermined order, one after another.
If one of the providers returns the schema, subsequent providers are not executed.

## Get Started

### Installation

The package requires PHP 8.2+.

```bash
composer require cycle/schema-provider
```

[![PHP](https://img.shields.io/packagist/php-v/cycle/schema-provider.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/schema-provider)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/cycle/schema-provider.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/cycle/schema-provider)
[![License](https://img.shields.io/packagist/l/cycle/schema-provider.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/cycle/schema-provider.svg?style=flat-square)](https://packagist.org/packages/cycle/schema-provider/stats)

### Usage

Let's explore a straightforward example of schema creation using this package. For example, we have a schema in
two php files **schema1.php** and **schema2.php**. In this scenario, we can use the
`Cycle\Schema\Provider\FromFilesSchemaProvider` to build the schema from multiple files. Before this provider,
we can add a `Cycle\Schema\Provider\SimpleCacheSchemaProvider`, capable of caching the schema. Upon subsequent schema
builds, this provider retrieves the schema from the cache, eliminating the need to build the schema using
`FromFilesSchemaProvider`.

```php
use Cycle\ORM\Schema;
use Cycle\Schema\Provider\FromFilesSchemaProvider;
use Cycle\Schema\Provider\SimpleCacheSchemaProvider;
use Cycle\Schema\Provider\Support\SchemaProviderPipeline;

$pipeline = (new SchemaProviderPipeline($container))->withConfig([
    SimpleCacheSchemaProvider::class => SimpleCacheSchemaProvider::config(key: 'cycle-schema'),
    FromFilesSchemaProvider::class => FromFilesSchemaProvider::config(files: [
        'runtime/schema1.php',
        'runtime/schema2.php',
    ]),
]);

$schema = new Schema($pipeline->read());
```

The `SimpleCacheSchemaProvider` requires an implementation of `Psr\SimpleCache\CacheInterface`, which must be defined
in your container. It uses this interface to retrieve and store the schema array. Alternatively, you can use the
`Cycle\Schema\Provider\PhpFileSchemaProvider`, which can save the schema to a PHP file.


### Building DB schema from different providers

To merge schema parts obtained from different providers, use `Cycle\Schema\Provider\MergeSchemaProvider`.

```php
use Cycle\ORM\Schema;
use Cycle\Schema\Provider\FromFilesSchemaProvider;
use Cycle\Schema\Provider\SimpleCacheSchemaProvider;
use Cycle\Schema\Provider\MergeSchemaProvider;
use Cycle\Schema\Provider\Support\SchemaProviderPipeline;

$pipeline = (new SchemaProviderPipeline($container))->withConfig([
    SimpleCacheSchemaProvider::class => SimpleCacheSchemaProvider::config(key: 'cycle-schema'),
    MergeSchemaProvider::class => [
        FromFilesSchemaProvider::class => FromFilesSchemaProvider::config(files: [
            'runtime/schema1.php',
            'runtime/schema2.php',
        ]),
        CustomSchemaProvider::class => ['some' => 'config'],
    ],
]);

$schema = new Schema($pipeline->read());
```
