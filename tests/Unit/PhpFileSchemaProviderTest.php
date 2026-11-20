<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit;

use Cycle\Schema\Provider\Exception\ConfigurationException;
use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Cycle\Schema\Provider\PhpFileSchemaProvider;
use Cycle\Schema\Provider\Tests\Fixtures\ArraySchemaProvider;
use Cycle\Schema\Provider\Tests\Fixtures\RecordingFiles;
use Spiral\Files\Files;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Core\Exception\SkipTest;
use Testo\Expect;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class PhpFileSchemaProviderTest extends BaseSchemaProvider
{
    protected const READ_CONFIG = ['file' => __DIR__ . '/../Fixtures/PhpFileSchemaProvider/simple_schema.php'];
    protected const READ_CONFIG_SCHEMA = ['user' => []];
    private const WRITE_CONFIG = ['file' => self::TMP_FILE];
    private const WRITE_ONLY_CONFIG = ['file' => self::TMP_FILE, 'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY];
    private const TMP_FILE = __DIR__ . '/../Fixtures/PhpFileSchemaProvider/write.php';

    private string $tmpDir;

    public function testConfig(): void
    {
        Assert::same(PhpFileSchemaProvider::config('foo'), ['file' => 'foo', 'mode' => PhpFileSchemaProvider::MODE_READ_AND_WRITE]);

        Assert::same(PhpFileSchemaProvider::config('foo', PhpFileSchemaProvider::MODE_WRITE_ONLY), ['file' => 'foo', 'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY]);
    }

    public function testReadFromNextProvider(): void
    {
        $provider1 = $this->createSchemaProvider(self::WRITE_CONFIG);
        $provider2 = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);

        $result = $provider1->read($provider2);

        Assert::same($result, self::READ_CONFIG_SCHEMA);
    }

    public function testDefaultState(): void
    {
        $provider = $this->createSchemaProvider();

        Assert::null($provider->read());
    }

    #[ExpectException(ConfigurationException::class)]
    public function testWithConfigWithoutRequiredParams(): void
    {
        $this->createSchemaProvider([]);
    }

    public function testWithConfigWithBadParams(): void
    {
        Expect::exception(SchemaProviderException::class)->withMessageMatchingRegex('/parameter must not be empty/');

        $nextProvider = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);

        $this->createSchemaProvider(null)->read($nextProvider);
    }

    public function testModeWriteOnlyWithoutSchemaFromNextProvider(): void
    {
        $provider = $this->createSchemaProvider(self::WRITE_ONLY_CONFIG);
        $nextProvider = new ArraySchemaProvider(null);

        Assert::null($provider->read($nextProvider));
        Assert::false(\file_exists(self::TMP_FILE), 'Empty schema file is created.');
    }

    public function testModeWriteOnlyWithSchemaFromNextProvider(): void
    {
        $provider = $this->createSchemaProvider(self::WRITE_ONLY_CONFIG);
        $nextProvider = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);
        Assert::same($provider->read($nextProvider), self::READ_CONFIG_SCHEMA);
        Assert::true(\file_exists(self::TMP_FILE), 'Schema file is not created.');
    }

    public function testModeWriteOnlyWithoutNextProviderException(): void
    {
        $config = self::READ_CONFIG;
        $config['mode'] = PhpFileSchemaProvider::MODE_WRITE_ONLY;
        $provider = $this->createSchemaProvider($config);

        Expect::exception(SchemaProviderException::class)
            ->withMessage(PhpFileSchemaProvider::class . ' can not read schema.');

        $provider->read();
    }

    public function testModeWriteOnlyExceptionOnRead(): void
    {
        $config = self::READ_CONFIG;
        $config['mode'] = PhpFileSchemaProvider::MODE_WRITE_ONLY;
        $provider = $this->createSchemaProvider($config);

        Expect::exception(SchemaProviderException::class);

        $provider->read();
    }

    public function testClear(): void
    {
        $this->prepareTmpFile();
        $provider = $this->createSchemaProvider(self::WRITE_CONFIG);

        $result = $provider->clear();

        Assert::true($result);
        Assert::false(\file_exists(self::TMP_FILE));
    }

    public function testClearNotExistingFile(): void
    {
        $provider = $this->createSchemaProvider(self::WRITE_CONFIG);

        $result = $provider->clear();

        Assert::true($result);
    }

    public function testClearNotAFile(): void
    {
        $files = new RecordingFiles();
        $provider = (new PhpFileSchemaProvider(files: $files))->withConfig(['file' => __DIR__]);

        $result = $provider->clear();

        Assert::false($result);
        Assert::array($files->calls)->notContains('delete');
        Assert::true(\is_dir(__DIR__));
    }

    public function testClearReadOnlyFile(): void
    {
        $file = $this->tmpDir . '/schema.php';
        (new Files())->write($file, '<?php return null;', ensureDirectory: true);
        \chmod($file, 0444);
        \clearstatcache();
        if (\is_writable($file)) {
            throw new SkipTest('The current user can write read-only files.');
        }
        $files = new RecordingFiles();
        $provider = (new PhpFileSchemaProvider(files: $files))->withConfig(['file' => $file]);

        $result = $provider->clear();
        // Windows refuses to delete a read-only file, so tearDown() could not remove the directory.
        \chmod($file, 0666);

        Assert::false($result);
        Assert::array($files->calls)->notContains('delete');
        Assert::true(\is_file($file));
    }

    public function testWritesThroughGivenFilesystem(): void
    {
        $files = new RecordingFiles();
        $provider = (new PhpFileSchemaProvider(files: $files))->withConfig(self::WRITE_CONFIG);

        $provider->read(new ArraySchemaProvider(self::READ_CONFIG_SCHEMA));

        Assert::array($files->calls)->contains('write');
    }

    public function testWritesIntoMissingDirectory(): void
    {
        $file = $this->tmpDir . '/nested/schema.php';
        $provider = $this->createSchemaProvider(['file' => $file]);

        $provider->read(new ArraySchemaProvider(self::READ_CONFIG_SCHEMA));

        Assert::same($this->createSchemaProvider(['file' => $file])->read(), self::READ_CONFIG_SCHEMA);
    }

    public function testWrittenFileIsWritableByEveryone(): void
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            throw new SkipTest('Windows has no POSIX file modes.');
        }
        $provider = $this->createSchemaProvider(self::WRITE_CONFIG);

        $provider->read(new ArraySchemaProvider(self::READ_CONFIG_SCHEMA));

        \clearstatcache();
        Assert::same(\fileperms(self::TMP_FILE) & 07777, 0777);
    }

    public function testPrepareTmpFile(): void
    {
        $this->prepareTmpFile();
        Assert::true(\file_exists(self::TMP_FILE));
    }

    public function testRemoveTmpFile(): void
    {
        $this->prepareTmpFile();
        $this->removeTmpFile();
        Assert::false(\file_exists(self::TMP_FILE));
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->removeTmpFile();
        $this->tmpDir = \sys_get_temp_dir() . '/cycle-schema-provider-' . \bin2hex(\random_bytes(4));
    }

    #[AfterTest]
    protected function tearDown(): void
    {
        $this->removeTmpFile();
        if (\is_dir($this->tmpDir)) {
            (new Files())->deleteDirectory($this->tmpDir);
        }
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
