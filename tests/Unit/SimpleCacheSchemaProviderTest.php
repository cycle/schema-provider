<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit;

use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Cycle\Schema\Provider\SimpleCacheSchemaProvider;
use Cycle\Schema\Provider\Tests\Fixtures\ArraySchemaProvider;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Test\Support\SimpleCache\Action;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Test\Support\SimpleCache\SimpleCacheActionLogger;

#[Test]
final class SimpleCacheSchemaProviderTest extends BaseSchemaProvider
{
    protected const READ_CONFIG = ['key' => self::CACHE_KEY];
    private const CACHE_KEY = 'test-cycle-schema-cache-key';

    private SimpleCacheActionLogger $cacheService;

    public function testConfig(): void
    {
        Assert::same(SimpleCacheSchemaProvider::config('foo'), ['key' => 'foo']);
    }

    public function testDefaultState(): void
    {
        $provider = $this->createSchemaProvider();

        Assert::null($provider->read());
        Assert::true($this->cacheService->has(self::CACHE_KEY));
    }

    public function testClear(): void
    {
        $provider = $this->createSchemaProvider(self::READ_CONFIG);

        $result = $provider->clear();

        Assert::true($result);
        Assert::same($this->cacheService->getActionKeyList(), [
            [Action::HAS, self::CACHE_KEY],
            [Action::DELETE, self::CACHE_KEY],
        ]);
        Assert::false($this->cacheService->has(self::CACHE_KEY));
    }

    public function testClearNotExistingKey(): void
    {
        $key = 'key-not-exists';
        $provider = $this->createSchemaProvider(['key' => $key]);

        $result = $provider->clear();

        Assert::true($result);
        Assert::same($this->cacheService->getActionKeyList(), [[Action::HAS, $key]]);
    }

    public function testClearWithCacheOnDeleteError(): void
    {
        $provider = $this->createSchemaProvider(self::READ_CONFIG);
        $this->cacheService->getCacheService()->returnOnDelete = false;

        Expect::exception(SchemaProviderException::class);

        $provider->clear();
    }

    public function testWriteOnReadFromNextProvider(): void
    {
        $key = 'key-not-exists';
        $provider = $this->createSchemaProvider(['key' => $key]);
        $nextProvider = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);

        $result = $provider->read($nextProvider);
        Assert::same($this->cacheService->getActionKeyList(), [[Action::GET, $key], [Action::SET, $key]]);
        Assert::same($result, self::READ_CONFIG_SCHEMA);
        Assert::same($this->cacheService->get($key), self::READ_CONFIG_SCHEMA);
    }

    #[BeforeTest]
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
