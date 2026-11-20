<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Fixtures;

use Spiral\Files\Files;
use Spiral\Files\FilesInterface;

/**
 * Spy over the real filesystem: every call is recorded by method name, then forwarded to {@see Files}.
 */
final class RecordingFiles implements FilesInterface
{
    /** @var list<non-empty-string> */
    public array $calls = [];

    private readonly Files $files;

    public function __construct()
    {
        $this->files = new Files();
    }

    public function ensureDirectory(string $directory, ?int $mode = null): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function read(string $filename): string
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function write(string $filename, string $data, ?int $mode = null, bool $ensureDirectory = false): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function append(string $filename, string $data, ?int $mode = null, bool $ensureDirectory = false): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function delete(string $filename): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function deleteDirectory(string $directory, bool $contentOnly = false): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function move(string $filename, string $destination): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function copy(string $filename, string $destination): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function touch(string $filename, ?int $mode = null): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function exists(string $filename): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function size(string $filename): int
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function extension(string $filename): string
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function md5(string $filename): string
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function time(string $filename): int
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function isDirectory(string $filename): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function isFile(string $filename): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function getPermissions(string $filename): int
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function setPermissions(string $filename, int $mode): bool
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function getFiles(string $location, ?string $pattern = null): array
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function tempFilename(string $extension = '', ?string $location = null): string
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function normalizePath(string $path, bool $asDirectory = false): string
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    public function relativePath(string $path, string $from): string
    {
        return $this->forward(__FUNCTION__, \func_get_args());
    }

    private function forward(string $method, array $args): mixed
    {
        $this->calls[] = $method;
        return $this->files->{$method}(...$args);
    }
}
