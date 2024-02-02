<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider;

use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Cycle\Schema\Provider\Support\BaseProviderCollector;
use Cycle\Schema\Provider\Support\SchemaMerger;

/**
 * A class for working with a group of schema providers.
 * Parts of the schema are read from all providers and merged into one.
 */
final class MergeSchemaProvider extends BaseProviderCollector
{
    protected const IS_SEQUENCE_PIPELINE = false;

    public function read(?SchemaProviderInterface $nextProvider = null): ?array
    {
        if ($this->providers === null) {
            throw new SchemaProviderException(self::class . ' is not configured.');
        }
        $parts = [];
        foreach ($this->providers as $provider) {
            $parts[] = $provider->read();
        }

        $schema = (new SchemaMerger())->merge(...$parts);

        if ($schema !== null || $nextProvider === null) {
            return $schema;
        }
        return $nextProvider->read();
    }
}
