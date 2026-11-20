<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit\Support;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Provider\Exception\BadDeclarationException;
use Cycle\Schema\Provider\Exception\CumulativeException;
use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Cycle\Schema\Provider\Tests\Fixtures\ArraySchemaProvider;
use Cycle\Schema\Provider\Tests\Fixtures\ConfigurableSchemaProvider;
use Cycle\Schema\Provider\Tests\Fixtures\SameOriginProvider;
use Cycle\Schema\Provider\Tests\Unit\BaseSchemaProvider;
use Psr\Container\ContainerInterface;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Test\Support\Container\SimpleContainer;

abstract class BaseProviderCollector extends BaseSchemaProvider
{
    protected const ANOTHER_SCHEMA = [
        'post' => [
            Schema::ENTITY => \stdClass::class,
            Schema::MAPPER => \stdClass::class,
        ],
    ];

    protected ContainerInterface $container;

    // Definition resolving test

    #[Test]
    public function testProviderFromString(): void
    {
        $this->prepareContainer(['provider' => new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);

        $provider = $this->createSchemaProvider(['provider']);

        Assert::same($provider->read(), self::READ_CONFIG_SCHEMA);
    }

    #[Test]
    public function testProviderFromArray(): void
    {
        $this->prepareContainer(['provider' => new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);
        $newSchema = self::ANOTHER_SCHEMA;

        $provider = $this->createSchemaProvider(['provider' => $newSchema]);

        Assert::same($provider->read(), $newSchema);
    }

    #[Test]
    public function testIgnoreStringKeyIfDefinitionIsNotArray(): void
    {
        $this->prepareContainer(['provider' => new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);

        $provider = $this->createSchemaProvider(['provider' => new ArraySchemaProvider(self::ANOTHER_SCHEMA)]);

        Assert::same($provider->read(), self::ANOTHER_SCHEMA);
    }

    #[Test]
    public function testProviderAsObject(): void
    {
        $provider = $this->createSchemaProvider([new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);

        Assert::same($provider->read(), self::READ_CONFIG_SCHEMA);
    }

    #[Test]
    public function testProviderAsBadClassObject(): void
    {
        $provider = $this->createSchemaProvider(['BadSchemaProvider']);

        Expect::exception(BadDeclarationException::class);

        $provider->read();
    }

    // Clear test

    #[Test]
    public function testClearWithoutProviders(): void
    {
        $provider = $this->createSchemaProvider([]);

        $result = $provider->clear();

        // exception was not thrown
        Assert::false($result);
    }

    #[Test]
    public function testClearNotConfigured(): void
    {
        $provider = $this->createSchemaProvider(null);

        Expect::exception(SchemaProviderException::class);

        $provider->clear();
    }

    #[Test]
    public function testClearResultFalse(): void
    {
        $provider1 = (new ConfigurableSchemaProvider(self::ANOTHER_SCHEMA))
            ->withConfig([ConfigurableSchemaProvider::OPTION_CLEARABLE => false]);
        $provider2 = (new ConfigurableSchemaProvider(self::ANOTHER_SCHEMA))
            ->withConfig([ConfigurableSchemaProvider::OPTION_CLEARABLE => false]);
        $provider3 = (new ConfigurableSchemaProvider(self::ANOTHER_SCHEMA))
            ->withConfig([ConfigurableSchemaProvider::OPTION_CLEARABLE => false]);

        $provider = $this->createSchemaProvider([$provider1, $provider2, $provider3]);

        $result = $provider->clear();

        // exception was not thrown
        Assert::false($result);
    }

    #[Test]
    public function testClearResultTrue(): void
    {
        $provider1 = (new ConfigurableSchemaProvider(self::ANOTHER_SCHEMA))
            ->withConfig([ConfigurableSchemaProvider::OPTION_CLEARABLE => false]);
        $provider2 = (new ConfigurableSchemaProvider(self::ANOTHER_SCHEMA))
            ->withConfig([ConfigurableSchemaProvider::OPTION_CLEARABLE => true]);
        $provider3 = (new ConfigurableSchemaProvider(self::ANOTHER_SCHEMA))
            ->withConfig([ConfigurableSchemaProvider::OPTION_CLEARABLE => false]);

        $provider = $this->createSchemaProvider([$provider1, $provider2, $provider3]);

        $result = $provider->clear();

        // exception was not thrown
        Assert::true($result);
    }

    #[Test]
    public function testClearAllProvidersIndependentFromWriteable(): void
    {
        $writeable = new ArraySchemaProvider(self::READ_CONFIG_SCHEMA);
        $origin = new SameOriginProvider([]);
        $notReadable = $origin->withConfig([]);
        $notWriteable1 = (new SameOriginProvider(self::READ_CONFIG_SCHEMA))
            ->withConfig([SameOriginProvider::OPTION_WRITABLE => false]);
        $notWriteable2 = (new SameOriginProvider(self::ANOTHER_SCHEMA))
            ->withConfig([SameOriginProvider::OPTION_CLEARABLE => false]);

        $provider = $this->createSchemaProvider([$writeable, $notWriteable1, $notReadable, $notWriteable2]);

        $provider->clear();

        Assert::null($writeable->read());
        Assert::null($notWriteable1->read());
        Assert::null($origin->read());
        Assert::same($notWriteable2->read(), self::ANOTHER_SCHEMA);
    }

    #[Test]
    public function testClearWithException(): void
    {
        $provider1 = (new SameOriginProvider(self::READ_CONFIG_SCHEMA))
            ->withConfig([SameOriginProvider::EXCEPTION_ON_CLEAR => true]);
        $provider2 = new ArraySchemaProvider(self::ANOTHER_SCHEMA);
        $provider3 = new ArraySchemaProvider(self::ANOTHER_SCHEMA);
        $provider = $this->createSchemaProvider([$provider1, $provider2, $provider3]);

        Expect::exception(CumulativeException::class);

        try {
            $provider->clear();
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // first provider throws exception
            Assert::same($provider1->read(), self::READ_CONFIG_SCHEMA);
            // next provider will be cleared
            Assert::null($provider2->read());
            // last provider will be cleared
            Assert::null($provider3->read());
        }
    }

    // Reading test

    #[Test]
    public function testReadNotConfigured(): void
    {
        $provider = $this->createSchemaProvider(null);

        Expect::exception(SchemaProviderException::class);

        $provider->read();
    }

    #[Test]
    public function testReadWithoutProviders(): void
    {
        $provider = $this->createSchemaProvider([]);

        Assert::null($provider->read());
    }

    #[Test]
    public function testReadWithException(): void
    {
        $this->prepareContainer([
            'withoutSchema' => (new SameOriginProvider(self::READ_CONFIG_SCHEMA))
                ->withConfig([SameOriginProvider::EXCEPTION_ON_READ => true]),
        ]);

        Expect::exception(SchemaProviderException::class);

        $this->createSchemaProvider(['withoutSchema'])->read();
    }

    #[Test]
    public function testReadFromOneProvider(): void
    {
        $provider = $this->createSchemaProvider([new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);

        $schema = $provider->read();

        Assert::same($schema, self::READ_CONFIG_SCHEMA);
    }

    #[Test]
    public function testReadingOrderWithNullIgnoring(): void
    {
        $this->prepareContainer([
            'withSchema' => new ArraySchemaProvider(self::READ_CONFIG_SCHEMA),
            'withoutSchema' => new ArraySchemaProvider(null),
        ]);

        $provider = $this->createSchemaProvider(['withoutSchema', 'withSchema']);

        Assert::notNull($provider->read());
    }

    #[Test]
    public function testReadWithAlternativeProvider(): void
    {
        $provider = $this->createSchemaProvider([
            new ArraySchemaProvider(),
            new ArraySchemaProvider(),
            new ArraySchemaProvider(),
        ]);

        $schema1 = $provider->read();
        $schema2 = $provider->read(new ArraySchemaProvider(self::READ_CONFIG_SCHEMA));

        Assert::null($schema1);
        Assert::same($schema2, self::READ_CONFIG_SCHEMA);
    }

    #[Test]
    public function testReadWithoutProvidersButWithAlternativeProvider(): void
    {
        $provider = $this->createSchemaProvider();

        $schema2 = $provider->read(new ArraySchemaProvider(self::READ_CONFIG_SCHEMA));

        Assert::same($schema2, self::READ_CONFIG_SCHEMA);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->prepareContainer();
    }

    protected function prepareContainer(array $definitions = []): ContainerInterface
    {
        return $this->container = new SimpleContainer(array_merge([
            ArraySchemaProvider::class => new ArraySchemaProvider(),
            'BadSchemaProvider' => new \stdClass(),
        ], $definitions));
    }
}
