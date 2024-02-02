<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Feature;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Provider\Exception\ConfigurationException;
use Cycle\Schema\Provider\Exception\DuplicateRoleException;
use Cycle\Schema\Provider\Exception\SchemaFileNotFoundException;
use Cycle\Schema\Provider\FromFilesSchemaProvider;

final class FromFilesSchemaProviderTest extends BaseSchemaProvider
{
    protected const READ_CONFIG = ['files' => [__DIR__ . '/Stub/FromFilesSchemaProvider/schema1.php']];
    protected const READ_CONFIG_SCHEMA = ['user' => []];

    public function testConfig(): void
    {
        $this->assertSame(
            ['files' => ['foo', 'bar'], 'strict' => false],
            FromFilesSchemaProvider::config(['foo', 'bar'])
        );

        $this->assertSame(
            ['files' => ['foo', 'bar'], 'strict' => true],
            FromFilesSchemaProvider::config(['foo', 'bar'], true)
        );
    }

    /**
     * @dataProvider emptyConfigProvider
     */
    public function testWithConfigEmpty(array $config): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Schema file list is not set.');
        $schemaProvider->withConfig($config);
    }

    public function testWithConfigInvalidFiles(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('The `files` parameter must be an array.');
        $schemaProvider->withConfig(['files' => __DIR__ . '/Stub/FromFilesSchemaProvider/schema1.php']);
    }

    /**
     * @dataProvider fileListBadValuesProvider
     */
    public function testWithConfigInvalidValueInFileList($value): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('The `files` parameter must contain non-empty string values.');
        $schemaProvider->withConfig(['files' => [$value]]);
    }

    public function testWithConfigInvalidStrict(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('The `strict` parameter must be a boolean.');
        $schemaProvider->withConfig([
            'files' => [__DIR__ . '/Stub/FromFilesSchemaProvider/schema1.php'],
            'strict' => 1,
        ]);
    }

    public function testWithConfig(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '/Stub/FromFilesSchemaProvider/schema1.php']])
            ->read();

        $this->assertSame(['user' => []], $data);
    }

    public function testWithConfigFilesNotExists(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '/Stub/FromFilesSchemaProvider/schema-not-exists.php']])
            ->read();

        $this->assertNull($data);
    }

    public function testWithConfigFilesEmpty(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '/Stub/FromFilesSchemaProvider/schema-empty.php']])
            ->read();

        $this->assertSame([], $data);
    }

    public function testWithConfigStrictFilesNotExists(): void
    {
        $schemaProvider = $this
            ->createSchemaProvider()
            ->withConfig([
                'files' => [
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema1.php',
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema-not-exists.php',
                ],
                'strict' => true,
            ]);

        $this->expectException(SchemaFileNotFoundException::class);
        $schemaProvider->read();
    }

    public function testRead(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig([
                'files' => [
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema1.php',
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema-not-exists.php', // not exists files should be silent in non strict mode
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema2.php',
                ],
            ])
            ->read();

        $this->assertSame([
            'user' => [],
            'post' => [Schema::DATABASE => 'postgres'],
            'comment' => [],
        ], $data);
    }

    public function testReadEmpty(): void
    {
        $schemaProvider = $this->createSchemaProvider();
        $this->assertNull($schemaProvider->read());
    }

    public function testReadDuplicateRoles(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $this->expectException(DuplicateRoleException::class);
        $this->expectExceptionMessage('The `post` role already exists in the DB schema.');
        $schemaProvider
            ->withConfig([
                'files' => [
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema2.php',
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema2-duplicate.php',
                ],
            ])
            ->read();
    }

    public function testReadWildcard(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig([
                'strict' => true,
                'files' => [
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema[12].php',
                    __DIR__ . '/Stub/FromFilesSchemaProvider/*/*.php', // no files found
                    __DIR__ . '/Stub/FromFilesSchemaProvider/**/level3*.php',
                ],
            ])
            ->read();

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('post', $data);
        $this->assertArrayHasKey('level3-schema', $data);
        $this->assertArrayHasKey('level3-1-schema', $data);
        $this->assertArrayHasKey('level3-2-schema', $data);
        $this->assertArrayNotHasKey('level2-schema', $data);
    }

    public function testClear(): void
    {
        $schemaProvider = $this->createSchemaProvider();
        $this->assertFalse($schemaProvider->clear());
    }

    public static function emptyConfigProvider(): \Traversable
    {
        yield [[]];
        yield [['files' => []]];
    }

    public static function fileListBadValuesProvider(): \Traversable
    {
        yield [null];
        yield [42];
        yield [STDIN];
        yield [[]];
        yield [['']];
        yield [new \SplFileInfo(__FILE__)];
    }

    protected function createSchemaProvider(array $config = null): FromFilesSchemaProvider
    {
        $provider = new FromFilesSchemaProvider();
        return $config === null ? $provider : $provider->withConfig($config);
    }
}
