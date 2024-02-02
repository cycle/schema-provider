<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider;

use Cycle\Schema\Provider\Exception\ConfigurationException;
use Cycle\Schema\Provider\Exception\SchemaFileNotFoundException;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobIterator;
use Cycle\Schema\Provider\Support\SchemaMerger;

/**
 * Be careful, using this class may be insecure.
 */
final class FromFilesSchemaProvider implements SchemaProviderInterface
{
    /**
     * @var array<non-empty-string> Schema files
     */
    private array $files = [];

    /**
     * @var bool Throw exception if file not found
     */
    private bool $strict = false;

    /**
     * @var \Closure(non-empty-string): non-empty-string
     */
    private \Closure $pathResolver;

    /**
     * @param null|callable(non-empty-string): non-empty-string $pathResolver A function that resolves
     *        framework-specific file paths.
     */
    public function __construct(?callable $pathResolver = null)
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->pathResolver = $pathResolver === null
            ? static fn (string $path): string => $path
            : \Closure::fromCallable($pathResolver);
    }

    /**
     * Create a configuration array for the {@see self::withConfig()} method.
     * @param array<non-empty-string> $files
     */
    public static function config(array $files, bool $strict = false): array
    {
        return [
            'files' => $files,
            'strict' => $strict,
        ];
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
            function (mixed $file) {
                if (!\is_string($file) || $file === '') {
                    throw new ConfigurationException('The `files` parameter must contain non-empty string values.');
                }
                return ($this->pathResolver)($file);
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
