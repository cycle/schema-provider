<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit\Support;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Provider\SchemaProviderInterface;
use Cycle\Schema\Provider\Support\DeferredSchemaProviderDecorator;
use Cycle\Schema\Provider\Tests\Fixtures\ArraySchemaProvider;
use Cycle\Schema\Provider\Tests\Fixtures\ConfigurableSchemaProvider;
use Cycle\Schema\Provider\Tests\Fixtures\SameOriginProvider;
use Cycle\Schema\Provider\Tests\Unit\BaseSchemaProvider;
use Psr\Container\ContainerInterface;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Test\Support\Container\SimpleContainer;

#[Test]
final class DeferredSchemaProviderDecoratorTest extends BaseSchemaProvider
{
    protected const READ_CONFIG = self::READ_CONFIG_SCHEMA;
    protected const ANOTHER_SCHEMA = [
        'post' => [
            Schema::ENTITY => \stdClass::class,
            Schema::MAPPER => \stdClass::class,
        ],
    ];
    protected const ALIASES = [
        '@test' => 'test',
    ];

    protected ContainerInterface $container;

    public function testReadWithLatestProvider(): void
    {
        $provider1 = new ArraySchemaProvider(null);
        $provider2 = new ArraySchemaProvider(null);
        $latestProvider = new ArraySchemaProvider(self::ANOTHER_SCHEMA);

        $deferred1 = $this->createSchemaProvider(null, $provider1);
        $deferred2 = $this->createSchemaProvider(null, $provider2, $deferred1);

        $result = $deferred2->read($latestProvider);

        Assert::same($result, self::ANOTHER_SCHEMA);
        Assert::same($provider1->read(), self::ANOTHER_SCHEMA);
        Assert::same($provider2->read(), self::ANOTHER_SCHEMA);
    }

    public function testNextDeferredProviderImmutabilityOnReadWithLatestProvider(): void
    {
        $this->prepareContainer([
            SameOriginProvider::class => (new SameOriginProvider(null))
                ->withConfig([SameOriginProvider::OPTION_WRITABLE => false]),
        ]);
        $latestProvider = new ArraySchemaProvider(self::ANOTHER_SCHEMA);

        $deferred1 = $this->createSchemaProvider(null, SameOriginProvider::class);
        $deferred2 = $this->createSchemaProvider(null, SameOriginProvider::class, $deferred1);

        $result1 = $deferred2->read($latestProvider);
        $result2 = $deferred2->read();

        Assert::same($result1, self::ANOTHER_SCHEMA);
        Assert::null($result2);
    }

    public function testUseSameProviderIfLatestProviderIsExists(): void
    {
        $provider1 = new ConfigurableSchemaProvider(null);
        $this->prepareContainer([ConfigurableSchemaProvider::class => $provider1]);
        $latestProvider = new ArraySchemaProvider(self::ANOTHER_SCHEMA);

        $deferred1 = $this->createSchemaProvider(
            [ConfigurableSchemaProvider::OPTION_WRITABLE => true],
            ConfigurableSchemaProvider::class,
        );
        $deferred2 = $this->createSchemaProvider(null, null, $deferred1);

        $result1 = $deferred2->read($latestProvider);
        $result2 = $deferred1->read();

        Assert::same($result1, self::ANOTHER_SCHEMA);
        Assert::same($result2, self::ANOTHER_SCHEMA);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->prepareContainer();
    }

    protected function prepareContainer(array $definitions = []): ContainerInterface
    {
        return $this->container = new SimpleContainer(array_merge([
            ArraySchemaProvider::class => new ArraySchemaProvider(self::ANOTHER_SCHEMA),
        ], $definitions));
    }

    /**
     * @param SchemaProviderInterface|string|null $provider
     */
    protected function createSchemaProvider(
        ?array $config = null,
        $provider = null,
        ?DeferredSchemaProviderDecorator $nextProvider = null,
    ): DeferredSchemaProviderDecorator {
        if ($provider === null) {
            $provider = new ArraySchemaProvider(self::DEFAULT_SCHEMA);
        }
        $provider = new DeferredSchemaProviderDecorator($this->container, $provider, $nextProvider);
        return $config === null ? $provider : $provider->withConfig($config);
    }
}
