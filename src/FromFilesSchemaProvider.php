<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider;

use Cycle\Schema\Provider\Exception\ConfigurationException;
use Cycle\Schema\Provider\Exception\SchemaFileNotFoundException;
use Cycle\Schema\Provider\Path\ResolverInterface;
use Cycle\Schema\Provider\Path\SimpleResolver;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobIterator;
use Cycle\Schema\Provider\Support\SchemaMerger;

/**
 * Be careful, using this class may be insecure.
 */
final class FromFilesSchemaProvider implements SchemaProviderInterface
{
    /**
     * @var array<string> Schema files
     */
    private array $files = [];

    /**
     * @var bool Throw exception if file not found
     */
    private bool $strict = false;

    private ResolverInterface $pathResolver;

    public function __construct(?ResolverInterface $resolver = null)
    {
        $this->pathResolver = $resolver ?? new SimpleResolver();
    }

    public function withConfig(array $config): self
    {
        $files = $config['files'] ?? [];
        if (!\is_array($files)) {
            throw new ConfigurationException('The `files` parameter must be an array.');
        }
        if (\count($files) === 0) {
            throw new ConfigurationException('Schema file list is not set.');
        }

        $strict = $config['strict'] ?? $this->strict;
        if (!\is_bool($strict)) {
            throw new ConfigurationException('The `strict` parameter must be a boolean.');
        }

        $files = \array_map(
            function ($file) {
                if (!\is_string($file)) {
                    throw new ConfigurationException('The `files` parameter must contain string values.');
                }
                return $this->pathResolver->resolve($file);
            },
            $files
        );

        $new = clone $this;
        $new->files = $files;
        $new->strict = $strict;
        return $new;
    }

    public function read(?SchemaProviderInterface $nextProvider = null): ?array
    {
        $schema = (new SchemaMerger())->merge(...$this->readFiles());

        return $schema !== null || $nextProvider === null ? $schema : $nextProvider->read();
    }

    public function clear(): bool
    {
        return false;
    }

    /**
     * Read schema from each file
     *
     * @return \Generator<int, array|null>
     */
    private function readFiles(): \Generator
    {
        foreach ($this->files as $path) {
            $path = \str_replace('\\', '/', $path);
            if (!Glob::isDynamic($path)) {
                yield $this->loadFile($path);
                continue;
            }
            foreach (new GlobIterator($path) as $file) {
                yield $this->loadFile($file);
            }
        }
    }

    private function loadFile(string $path): ?array
    {
        $isFile = \is_file($path);

        if (!$isFile && $this->strict) {
            throw new SchemaFileNotFoundException($path);
        }

        return $isFile ? require $path : null;
    }
}
