<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider;

use Cycle\Schema\Provider\Exception\ConfigurationException;
use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Cycle\Schema\Provider\Path\ResolverInterface;
use Cycle\Schema\Provider\Path\SimpleResolver;
use Cycle\Schema\Renderer\PhpSchemaRenderer;
use Spiral\Files\Files;
use Spiral\Files\FilesInterface;

final class PhpFileSchemaProvider implements SchemaProviderInterface
{
    public const MODE_READ_AND_WRITE = 0;
    public const MODE_WRITE_ONLY = 1;

    private string $file = '';
    private int $mode = self::MODE_READ_AND_WRITE;

    private ResolverInterface $pathResolver;
    private FilesInterface $files;

    public function __construct(?ResolverInterface $resolver = null, ?FilesInterface $files = null)
    {
        $this->pathResolver = $resolver ?? new SimpleResolver();
        $this->files = $files ?? new Files();
    }

    public function withConfig(array $config): self
    {
        $new = clone $this;

        // required option
        if ($this->file === '' && !array_key_exists('file', $config)) {
            throw new ConfigurationException('The `file` parameter is required.');
        }
        $new->file = $this->pathResolver->resolve($config['file']);

        $new->mode = $config['mode'] ?? $this->mode;

        return $new;
    }

    public function read(?SchemaProviderInterface $nextProvider = null): ?array
    {
        if (!$this->isReadable()) {
            if ($nextProvider === null) {
                throw new SchemaProviderException(__CLASS__ . ' can not read schema.');
            }
            $schema = null;
        } else {
            /** @psalm-suppress UnresolvableInclude */
            $schema = !$this->files->isFile($this->file) ? null : (include $this->file);
        }

        if ($schema !== null || $nextProvider === null) {
            return $schema;
        }

        $schema = $nextProvider->read();
        if ($schema !== null) {
            $this->write($schema);
        }
        return $schema;
    }

    private function write(array $schema): bool
    {
        if (\basename($this->file) === '') {
            throw new SchemaProviderException('The `file` parameter must not be empty.');
        }

        $content = (new PhpSchemaRenderer())->render($schema);
        $this->files->write($this->file, $content, 0777, true);

        return true;
    }

    private function removeFile(): bool
    {
        if (!$this->files->exists($this->file)) {
            return true;
        }
        if (!$this->files->isFile($this->file)) {
            throw new SchemaProviderException(\sprintf('`%s` is not a file.', $this->file));
        }
        if (!\is_writable($this->file)) {
            throw new SchemaProviderException(\sprintf('File `%s` is not writeable.', $this->file));
        }

        return $this->files->delete($this->file);
    }

    public function clear(): bool
    {
        try {
            return $this->removeFile();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isReadable(): bool
    {
        return $this->mode !== self::MODE_WRITE_ONLY;
    }
}
