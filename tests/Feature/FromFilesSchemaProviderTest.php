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

    #[\Testo\Test]
    public function testConfig(): void
    {
        \Testo\Assert::same(FromFilesSchemaProvider::config(['foo', 'bar']), ['files' => ['foo', 'bar'], 'strict' => false]);

        \Testo\Assert::same(FromFilesSchemaProvider::config(['foo', 'bar'], true), ['files' => ['foo', 'bar'], 'strict' => true]);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('emptyConfigProvider')]
    public function testWithConfigEmpty(array $config): void
    {
        $schemaProvider = $this->createSchemaProvider();

        \Testo\Expect::exception(ConfigurationException::class)->withMessageContaining('Schema file list is not set.');
        $schemaProvider->withConfig($config);
    }

    #[\Testo\Test]
    public function testWithConfigInvalidFiles(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        \Testo\Expect::exception(ConfigurationException::class)->withMessageContaining('The `files` parameter must be an array.');
        $schemaProvider->withConfig(['files' => __DIR__ . '/Stub/FromFilesSchemaProvider/schema1.php']);
    }

    /**
     * @param mixed $value
     */
    #[\Testo\Test]
    #[\Testo\Data\DataProvider('fileListBadValuesProvider')]
    public function testWithConfigInvalidValueInFileList($value): void
    {
        $schemaProvider = $this->createSchemaProvider();

        \Testo\Expect::exception(ConfigurationException::class)->withMessageContaining('The `files` parameter must contain non-empty string values.');
        $schemaProvider->withConfig(['files' => [$value]]);
    }

    #[\Testo\Test]
    public function testWithConfigInvalidStrict(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        \Testo\Expect::exception(ConfigurationException::class)->withMessageContaining('The `strict` parameter must be a boolean.');
        $schemaProvider->withConfig([
            'files' => [__DIR__ . '/Stub/FromFilesSchemaProvider/schema1.php'],
            'strict' => 1,
        ]);
    }

    #[\Testo\Test]
    public function testWithConfig(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '/Stub/FromFilesSchemaProvider/schema1.php']])
            ->read();

        \Testo\Assert::same($data, ['user' => []]);
    }

    #[\Testo\Test]
    public function testWithConfigFilesNotExists(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '/Stub/FromFilesSchemaProvider/schema-not-exists.php']])
            ->read();

        \Testo\Assert::null($data);
    }

    #[\Testo\Test]
    public function testWithConfigFilesEmpty(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '/Stub/FromFilesSchemaProvider/schema-empty.php']])
            ->read();

        \Testo\Assert::same($data, []);
    }

    #[\Testo\Test]
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

        \Testo\Expect::exception(SchemaFileNotFoundException::class);
        $schemaProvider->read();
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($data, [
            'user' => [],
            'post' => [Schema::DATABASE => 'postgres'],
            'comment' => [],
        ]);
    }

    #[\Testo\Test]
    public function testReadEmpty(): void
    {
        $schemaProvider = $this->createSchemaProvider();
        \Testo\Assert::null($schemaProvider->read());
    }

    #[\Testo\Test]
    public function testReadDuplicateRoles(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        \Testo\Expect::exception(DuplicateRoleException::class)->withMessageContaining('The `post` role already exists in the DB schema.');
        $schemaProvider
            ->withConfig([
                'files' => [
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema2.php',
                    __DIR__ . '/Stub/FromFilesSchemaProvider/schema2-duplicate.php',
                ],
            ])
            ->read();
    }

    #[\Testo\Test]
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

        \Testo\Assert::array($data)->hasKeys('user')->hasKeys('post')->hasKeys('level3-schema')->hasKeys('level3-1-schema')->hasKeys('level3-2-schema')->doesNotHaveKeys('level2-schema');
    }

    #[\Testo\Test]
    public function testClear(): void
    {
        $schemaProvider = $this->createSchemaProvider();
        \Testo\Assert::false($schemaProvider->clear());
    }

    protected function createSchemaProvider(?array $config = null): FromFilesSchemaProvider
    {
        $provider = new FromFilesSchemaProvider();
        return $config === null ? $provider : $provider->withConfig($config);
    }
}
