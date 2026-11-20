<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Feature\Support;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Provider\SchemaProviderInterface;
use Cycle\Schema\Provider\Support\DeferredSchemaProviderDecorator;
use Cycle\Schema\Provider\Tests\Feature\BaseSchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Stub\ArraySchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Stub\ConfigurableSchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Stub\SameOriginProvider;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;

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

    #[\Testo\Test]
    public function testReadWithLatestProvider()
    {
        $provider1 = new ArraySchemaProvider(null);
        $provider2 = new ArraySchemaProvider(null);
        $latestProvider = new ArraySchemaProvider(self::ANOTHER_SCHEMA);

        $deferred1 = $this->createSchemaProvider(null, $provider1);
        $deferred2 = $this->createSchemaProvider(null, $provider2, $deferred1);

        $result = $deferred2->read($latestProvider);

        \Testo\Assert::same($result, self::ANOTHER_SCHEMA);
        \Testo\Assert::same($provider1->read(), self::ANOTHER_SCHEMA);
        \Testo\Assert::same($provider2->read(), self::ANOTHER_SCHEMA);
    }

    #[\Testo\Test]
    public function testNextDeferredProviderImmutabilityOnReadWithLatestProvider()
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

        \Testo\Assert::same($result1, self::ANOTHER_SCHEMA);
        \Testo\Assert::null($result2);
    }

    #[\Testo\Test]
    public function testUseSameProviderIfLatestProviderIsExists()
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

        \Testo\Assert::same($result1, self::ANOTHER_SCHEMA);
        \Testo\Assert::same($result2, self::ANOTHER_SCHEMA);
    }

    #[\Testo\Lifecycle\BeforeTest]
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
