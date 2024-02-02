# Cycle ORM - Schema Provider

[![PHP Version Require](https://poser.pugx.org/cycle/schema-provider/require/php)](https://packagist.org/packages/cycle/schema-provider)
[![Latest Stable Version](https://poser.pugx.org/cycle/schema-provider/v/stable)](https://packagist.org/packages/cycle/schema-provider)
[![phpunit](https://github.com/cycle/schema-provider/actions/workflows/phpunit.yml/badge.svg)](https://github.com/cycle/schema-provider/actions)
[![psalm](https://github.com/cycle/schema-provider/actions/workflows/psalm.yml/badge.svg)](https://github.com/cycle/schema-provider/actions)
[![Total Downloads](https://poser.pugx.org/cycle/schema-provider/downloads)](https://packagist.org/packages/cycle/schema-provider)
[![psalm-level](https://shepherd.dev/github/cycle/schema-provider/level.svg)](https://shepherd.dev/github/cycle/schema-provider)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/schema-provider/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/cycle/schema-provider/?branch=1.x)
[![Codecov](https://codecov.io/gh/cycle/schema-provider/graph/badge.svg)](https://codecov.io/gh/cycle/schema-provider)
<a href="https://discord.gg/TFeEmCs"><img src="https://img.shields.io/badge/discord-chat-magenta.svg"></a>

[Cycle ORM](https://github.com/cycle/orm) uses an object implementing the `Cycle\ORM\SchemaInterface` interface as a schema.
This schema can be constructed from a PHP array with a specific structure. The package at hand offers a comprehensive
solution for building a schema from different sources. It includes a collection of providers that implements the
`Cycle\Schema\Provider\SchemaProviderInterface` interface. These providers are grouped in the
`Cycle\Schema\Provider\Support\SchemaProviderPipeline`.

This pipeline orchestrates the execution of providers in a predetermined order, one after another.
If one of the providers returns the schema, subsequent providers are not executed.

## Requirements

Make sure that your server is configured with the following PHP versions and extensions:

- PHP >=8.0

## Installation

You can install the package via Composer:

```bash
composer require cycle/schema-provider
```

## Usage

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

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.
Maintained by [Spiral Scout](https://spiralscout.com).
