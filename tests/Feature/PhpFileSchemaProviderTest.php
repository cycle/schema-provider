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

    #[\Testo\Test]
    public function testConfig(): void
    {
        \Testo\Assert::same(PhpFileSchemaProvider::config('foo'), ['file' => 'foo', 'mode' => PhpFileSchemaProvider::MODE_READ_AND_WRITE]);

        \Testo\Assert::same(PhpFileSchemaProvider::config('foo', PhpFileSchemaProvider::MODE_WRITE_ONLY), ['file' => 'foo', 'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY]);
    }

    #[\Testo\Test]
    public function testReadFromNextProvider(): void
    {
        $provider1 = $this->createSchemaProvider(self::WRITE_CONFIG);
        $provider2 = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);

        $result = $provider1->read($provider2);

        \Testo\Assert::same($result, self::READ_CONFIG_SCHEMA);
    }

    #[\Testo\Test]
    public function testDefaultState(): void
    {
        $provider = $this->createSchemaProvider();

        \Testo\Assert::null($provider->read());
    }

    #[\Testo\Test]
    public function testWithConfigWithoutRequiredParams(): void
    {
        \Testo\Expect::exception(ConfigurationException::class);

        $this->createSchemaProvider([]);
    }

    #[\Testo\Test]
    public function testWithConfigWithBadParams(): void
    {
        \Testo\Expect::exception(SchemaProviderException::class)->withMessageMatchingRegex('/parameter must not be empty/');

        $nextProvider = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);

        $this->createSchemaProvider(null)->read($nextProvider);
    }

    #[\Testo\Test]
    public function testModeWriteOnlyWithoutSchemaFromNextProvider(): void
    {
        $provider = $this->createSchemaProvider(self::WRITE_ONLY_CONFIG);
        $nextProvider = new ArraySchemaProvider(null);

        \Testo\Assert::null($provider->read($nextProvider));
        \Testo\Assert::false(\file_exists(self::TMP_FILE), 'Empty schema file is created.');
    }

    #[\Testo\Test]
    public function testModeWriteOnlyWithSchemaFromNextProvider(): void
    {
        $provider = $this->createSchemaProvider(self::WRITE_ONLY_CONFIG);
        $nextProvider = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);
        \Testo\Assert::same($provider->read($nextProvider), self::READ_CONFIG_SCHEMA);
        \Testo\Assert::true(\file_exists(self::TMP_FILE), 'Schema file is not created.');
    }

    #[\Testo\Test]
    public function testModeWriteOnlyWithoutNextProviderException(): void
    {
        $config = self::READ_CONFIG;
        $config['mode'] = PhpFileSchemaProvider::MODE_WRITE_ONLY;
        $provider = $this->createSchemaProvider($config);

        \Testo\Expect::exception(SchemaProviderException::class);

        $provider->read();
    }

    #[\Testo\Test]
    public function testModeWriteOnlyExceptionOnRead(): void
    {
        $config = self::READ_CONFIG;
        $config['mode'] = PhpFileSchemaProvider::MODE_WRITE_ONLY;
        $provider = $this->createSchemaProvider($config);

        \Testo\Expect::exception(SchemaProviderException::class);

        $provider->read();
    }

    #[\Testo\Test]
    public function testClear(): void
    {
        $this->prepareTmpFile();
        $provider = $this->createSchemaProvider(self::WRITE_CONFIG);

        $result = $provider->clear();

        \Testo\Assert::true($result);
        \Testo\Assert::false(\file_exists(self::TMP_FILE));
    }

    #[\Testo\Test]
    public function testClearNotExistingFile(): void
    {
        $provider = $this->createSchemaProvider(self::WRITE_CONFIG);

        $result = $provider->clear();

        \Testo\Assert::true($result);
    }

    #[\Testo\Test]
    public function testClearNotAFile(): void
    {
        $provider = $this->createSchemaProvider(['file' => __DIR__]);

        $result = $provider->clear();

        \Testo\Assert::false($result);
    }

    #[\Testo\Test]
    public function testPrepareTmpFile(): void
    {
        $this->prepareTmpFile();
        \Testo\Assert::true(\file_exists(self::TMP_FILE));
    }

    #[\Testo\Test]
    public function testRemoveTmpFile(): void
    {
        $this->prepareTmpFile();
        $this->removeTmpFile();
        \Testo\Assert::false(\file_exists(self::TMP_FILE));
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->removeTmpFile();
    }

    #[\Testo\Lifecycle\AfterTest]
    protected function tearDown(): void
    {
        $this->removeTmpFile();
    }

    protected function createSchemaProvider(?array $config = null): PhpFileSchemaProvider
    {
        $provider = new PhpFileSchemaProvider();
        return $config === null ? $provider : $provider->withConfig($config);
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
}
