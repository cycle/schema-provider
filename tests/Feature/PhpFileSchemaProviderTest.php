<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Feature;

use Cycle\Schema\Provider\Exception\ConfigurationException;
use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Cycle\Schema\Provider\PhpFileSchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Stub\ArraySchemaProvider;

final class PhpFileSchemaProviderTest extends BaseSchemaProvider
{
    protected const READ_CONFIG = ['file' => __DIR__ . '/Stub/PhpFileSchemaProvider/simple_schema.php'];
    protected const READ_CONFIG_SCHEMA = ['user' => []];
    private const WRITE_CONFIG = ['file' => self::TMP_FILE];
    private const WRITE_ONLY_CONFIG = ['file' => self::TMP_FILE, 'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY];
    private const TMP_FILE = __DIR__ . '/Stub/PhpFileSchemaProvider/write.php';

    protected function setUp(): void
    {
        $this->removeTmpFile();
    }

    protected function tearDown(): void
    {
        $this->removeTmpFile();
    }

    public function testConfig(): void
    {
        $this->assertSame(
            ['file' => 'foo', 'mode' => PhpFileSchemaProvider::MODE_READ_AND_WRITE],
            PhpFileSchemaProvider::config('foo')
        );

        $this->assertSame(
            ['file' => 'foo', 'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY],
            PhpFileSchemaProvider::config('foo', PhpFileSchemaProvider::MODE_WRITE_ONLY)
        );
    }

    public function testReadFromNextProvider(): void
    {
        $provider1 = $this->createSchemaProvider(self::WRITE_CONFIG);
        $provider2 = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);

        $result = $provider1->read($provider2);

        $this->assertSame(self::READ_CONFIG_SCHEMA, $result);
    }

    public function testDefaultState(): void
    {
        $provider = $this->createSchemaProvider();

        $this->assertNull($provider->read());
    }

    public function testWithConfigWithoutRequiredParams(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->createSchemaProvider([]);
    }

    public function testWithConfigWithBadParams(): void
    {
        $this->expectException(SchemaProviderException::class);
        $this->expectExceptionMessageMatches('/parameter must not be empty/');

        $nextProvider = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);

        $this->createSchemaProvider(null)->read($nextProvider);
    }

    public function testModeWriteOnlyWithoutSchemaFromNextProvider(): void
    {
        $provider = $this->createSchemaProvider(self::WRITE_ONLY_CONFIG);
        $nextProvider = new ArraySchemaProvider(null);

        $this->assertNull($provider->read($nextProvider));
        $this->assertFileDoesNotExist(self::TMP_FILE, 'Empty schema file is created.');
    }

    public function testModeWriteOnlyWithSchemaFromNextProvider(): void
    {
        $provider = $this->createSchemaProvider(self::WRITE_ONLY_CONFIG);
        $nextProvider = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);
        $this->assertSame(self::READ_CONFIG_SCHEMA, $provider->read($nextProvider));
        $this->assertFileExists(self::TMP_FILE, 'Schema file is not created.');
    }

    public function testModeWriteOnlyWithoutNextProviderException(): void
    {
        $config = self::READ_CONFIG;
        $config['mode'] = PhpFileSchemaProvider::MODE_WRITE_ONLY;
        $provider = $this->createSchemaProvider($config);

        $this->expectException(SchemaProviderException::class);

        $provider->read();
    }

    public function testModeWriteOnlyExceptionOnRead(): void
    {
        $config = self::READ_CONFIG;
        $config['mode'] = PhpFileSchemaProvider::MODE_WRITE_ONLY;
        $provider = $this->createSchemaProvider($config);

        $this->expectException(SchemaProviderException::class);

        $provider->read();
    }

    public function testClear(): void
    {
        $this->prepareTmpFile();
        $provider = $this->createSchemaProvider(self::WRITE_CONFIG);

        $result = $provider->clear();

        $this->assertTrue($result);
        $this->assertFileDoesNotExist(self::TMP_FILE);
    }

    public function testClearNotExistingFile(): void
    {
        $provider = $this->createSchemaProvider(self::WRITE_CONFIG);

        $result = $provider->clear();

        $this->assertTrue($result);
    }

    public function testClearNotAFile(): void
    {
        $provider = $this->createSchemaProvider(['file' => __DIR__]);

        $result = $provider->clear();

        $this->assertFalse($result);
    }

    public function testPrepareTmpFile(): void
    {
        $this->prepareTmpFile();
        $this->assertFileExists(self::TMP_FILE);
    }

    public function testRemoveTmpFile(): void
    {
        $this->prepareTmpFile();
        $this->removeTmpFile();
        $this->assertFileDoesNotExist(self::TMP_FILE);
    }

    private function prepareTmpFile(): void
    {
        \file_put_contents(self::TMP_FILE, '<?php return null;');
    }

    private function removeTmpFile(): void
    {
        if (\is_file(self::TMP_FILE)) {
            \unlink(self::TMP_FILE);
        }
    }

    protected function createSchemaProvider(array $config = null): PhpFileSchemaProvider
    {
        $provider = new PhpFileSchemaProvider();
        return $config === null ? $provider : $provider->withConfig($config);
    }
}
