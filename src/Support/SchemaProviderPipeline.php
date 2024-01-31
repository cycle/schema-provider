<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Support;

use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Cycle\Schema\Provider\SchemaProviderInterface;

/**
 * A class for working with a group of schema providers.
 * When the schema is read, it queues the specified schema providers using the {@see DeferredSchemaProviderDecorator}.
 */
final class SchemaProviderPipeline extends BaseProviderCollector
{
    public function read(?SchemaProviderInterface $nextProvider = null): ?array
    {
        if ($this->providers === null) {
            throw new SchemaProviderException(self::class . ' is not configured.');
        }
        if ($this->providers->count() === 0) {
            return $nextProvider?->read();
        }

        return $this->providers[0]->read($nextProvider);
    }
}
