<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Provider\Exception\ConfigurationException;
use Cycle\Schema\Provider\Exception\DuplicateRoleException;
use Cycle\Schema\Provider\Exception\SchemaFileNotFoundException;
use Cycle\Schema\Provider\FromFilesSchemaProvider;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
final class FromFilesSchemaProviderTest extends BaseSchemaProvider
{
    protected const READ_CONFIG = ['files' => [__DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema1.php']];
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

    public function testConfig(): void
    {
        Assert::same(FromFilesSchemaProvider::config(['foo', 'bar']), ['files' => ['foo', 'bar'], 'strict' => false]);

        Assert::same(FromFilesSchemaProvider::config(['foo', 'bar'], true), ['files' => ['foo', 'bar'], 'strict' => true]);
    }

    #[DataProvider('emptyConfigProvider')]
    public function testWithConfigEmpty(array $config): void
    {
        $schemaProvider = $this->createSchemaProvider();

        Expect::exception(ConfigurationException::class)->withMessageContaining('Schema file list is not set.');
        $schemaProvider->withConfig($config);
    }

    public function testWithConfigInvalidFiles(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        Expect::exception(ConfigurationException::class)->withMessageContaining('The `files` parameter must be an array.');
        $schemaProvider->withConfig(['files' => __DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema1.php']);
    }

    /**
     * @param mixed $value
     */
    #[DataProvider('fileListBadValuesProvider')]
    public function testWithConfigInvalidValueInFileList($value): void
    {
        $schemaProvider = $this->createSchemaProvider();

        Expect::exception(ConfigurationException::class)->withMessageContaining('The `files` parameter must contain non-empty string values.');
        $schemaProvider->withConfig(['files' => [$value]]);
    }

    public function testWithConfigInvalidStrict(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        Expect::exception(ConfigurationException::class)->withMessageContaining('The `strict` parameter must be a boolean.');
        $schemaProvider->withConfig([
            'files' => [__DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema1.php'],
            'strict' => 1,
        ]);
    }

    public function testWithConfig(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema1.php']])
            ->read();

        Assert::same($data, ['user' => []]);
    }

    public function testWithConfigFilesNotExists(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema-not-exists.php']])
            ->read();

        Assert::null($data);
    }

    public function testWithConfigFilesEmpty(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema-empty.php']])
            ->read();

        Assert::same($data, []);
    }

    public function testWithConfigStrictFilesNotExists(): void
    {
        $schemaProvider = $this
            ->createSchemaProvider()
            ->withConfig([
                'files' => [
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema1.php',
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema-not-exists.php',
                ],
                'strict' => true,
            ]);

        Expect::exception(SchemaFileNotFoundException::class)->withMessage(\sprintf(
            'Schema file `%s` not found.',
            \str_replace('\\', '/', __DIR__) . '/../Fixtures/FromFilesSchemaProvider/schema-not-exists.php',
        ));
        $schemaProvider->read();
    }

    public function testReadWithBackslashSeparators(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig(['files' => [__DIR__ . '\\..\\Fixtures\\FromFilesSchemaProvider\\schema1.php'], 'strict' => true])
            ->read();

        Assert::same($data, ['user' => []]);
    }

    public function testRead(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        $data = $schemaProvider
            ->withConfig([
                'files' => [
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema1.php',
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema-not-exists.php', // not exists files should be silent in non strict mode
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema2.php',
                ],
            ])
            ->read();

        Assert::same($data, [
            'user' => [],
            'post' => [Schema::DATABASE => 'postgres'],
            'comment' => [],
        ]);
    }

    public function testReadEmpty(): void
    {
        $schemaProvider = $this->createSchemaProvider();
        Assert::null($schemaProvider->read());
    }

    public function testReadDuplicateRoles(): void
    {
        $schemaProvider = $this->createSchemaProvider();

        Expect::exception(DuplicateRoleException::class)->withMessageContaining('The `post` role already exists in the DB schema.');
        $schemaProvider
            ->withConfig([
                'files' => [
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema2.php',
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema2-duplicate.php',
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
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/schema[12].php',
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/*/*.php', // no files found
                    __DIR__ . '/../Fixtures/FromFilesSchemaProvider/**/level3*.php',
                ],
            ])
            ->read();

        Assert::array($data)->hasKeys('user')->hasKeys('post')->hasKeys('level3-schema')->hasKeys('level3-1-schema')->hasKeys('level3-2-schema')->doesNotHaveKeys('level2-schema');
    }

    public function testClear(): void
    {
        $schemaProvider = $this->createSchemaProvider();
        Assert::false($schemaProvider->clear());
    }

    protected function createSchemaProvider(?array $config = null): FromFilesSchemaProvider
    {
        $provider = new FromFilesSchemaProvider();
        return $config === null ? $provider : $provider->withConfig($config);
    }
}
