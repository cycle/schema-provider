<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider;

use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Psr\SimpleCache\CacheInterface;

final class SimpleCacheSchemaProvider implements SchemaProviderInterface
{
    public const DEFAULT_KEY = 'Cycle-ORM-Schema';
    private string $key = self::DEFAULT_KEY;

    public function __construct(
        private CacheInterface $cache
    ) {
    }

    public function withConfig(array $config): self
    {
        $new = clone $this;
        $new->key = $config['key'] ?? self::DEFAULT_KEY;
        return $new;
    }

    public function read(?SchemaProviderInterface $nextProvider = null): ?array
    {
        $schema = $this->cache->get($this->key);

        if ($schema !== null || $nextProvider === null) {
            return $schema;
        }

        $schema = $nextProvider->read();
        if ($schema !== null) {
            $this->write($schema);
        }
        return $schema;
    }

    public function clear(): bool
    {
        if (!$this->cache->has($this->key)) {
            return true;
        }
        $result = $this->cache->delete($this->key);
        if ($result === false) {
            throw new SchemaProviderException(\sprintf('Unable to delete `%s` from cache.', $this->key));
        }
        return true;
    }

    private function write(array $schema): bool
    {
        return $this->cache->set($this->key, $schema);
    }
}
