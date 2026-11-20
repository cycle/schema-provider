<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Feature;

use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Cycle\Schema\Provider\SimpleCacheSchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Stub\ArraySchemaProvider;
use Yiisoft\Test\Support\SimpleCache\Action;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Test\Support\SimpleCache\SimpleCacheActionLogger;

final class SimpleCacheSchemaProviderTest extends BaseSchemaProvider
{
    protected const READ_CONFIG = ['key' => self::CACHE_KEY];
    private const CACHE_KEY = 'test-cycle-schema-cache-key';

    private SimpleCacheActionLogger $cacheService;

    #[\Testo\Test]
    public function testConfig(): void
    {
        \Testo\Assert::same(SimpleCacheSchemaProvider::config('foo'), ['key' => 'foo']);
    }

    #[\Testo\Test]
    public function testDefaultState(): void
    {
        $provider = $this->createSchemaProvider();

        \Testo\Assert::null($provider->read());
        \Testo\Assert::true($this->cacheService->has(self::CACHE_KEY));
    }

    #[\Testo\Test]
    public function testClear(): void
    {
        $provider = $this->createSchemaProvider(self::READ_CONFIG);

        $result = $provider->clear();

        \Testo\Assert::true($result);
        \Testo\Assert::same($this->cacheService->getActionKeyList(), [
            [Action::HAS, self::CACHE_KEY],
            [Action::DELETE, self::CACHE_KEY],
        ]);
        \Testo\Assert::false($this->cacheService->has(self::CACHE_KEY));
    }

    #[\Testo\Test]
    public function testClearNotExistingKey(): void
    {
        $key = 'key-not-exists';
        $provider = $this->createSchemaProvider(['key' => $key]);

        $result = $provider->clear();

        \Testo\Assert::true($result);
        \Testo\Assert::same($this->cacheService->getActionKeyList(), [[Action::HAS, $key]]);
    }

    #[\Testo\Test]
    public function testClearWithCacheOnDeleteError(): void
    {
        $provider = $this->createSchemaProvider(self::READ_CONFIG);
        $this->cacheService->getCacheService()->returnOnDelete = false;

        \Testo\Expect::exception(SchemaProviderException::class);

        $provider->clear();
    }

    #[\Testo\Test]
    public function testWriteOnReadFromNextProvider(): void
    {
        $key = 'key-not-exists';
        $provider = $this->createSchemaProvider(['key' => $key]);
        $nextProvider = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);

        $result = $provider->read($nextProvider);
        \Testo\Assert::same($this->cacheService->getActionKeyList(), [[Action::GET, $key], [Action::SET, $key]]);
        \Testo\Assert::same($result, self::READ_CONFIG_SCHEMA);
        \Testo\Assert::same($this->cacheService->get($key), self::READ_CONFIG_SCHEMA);
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->prepareCacheService();
    }

    protected function createSchemaProvider(?array $config = null): SimpleCacheSchemaProvider
    {
        $provider = new SimpleCacheSchemaProvider($this->cacheService);
        return $config === null ? $provider : $provider->withConfig($config);
    }

    private function prepareCacheService(): void
    {
        $this->cacheService = new SimpleCacheActionLogger(
            new MemorySimpleCache(),
            [self::CACHE_KEY => self::READ_CONFIG_SCHEMA],
        );
    }
}
