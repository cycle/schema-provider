<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Feature\Support;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Provider\Exception\BadDeclarationException;
use Cycle\Schema\Provider\Exception\CumulativeException;
use Cycle\Schema\Provider\Exception\SchemaProviderException;
use Cycle\Schema\Provider\Tests\Feature\BaseSchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Stub\ArraySchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Stub\ConfigurableSchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Stub\SameOriginProvider;
use Psr\Container\ContainerInterface;
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

    #[\Testo\Test]
    public function testProviderFromString(): void
    {
        $this->prepareContainer(['provider' => new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);

        $provider = $this->createSchemaProvider(['provider']);

        \Testo\Assert::same($provider->read(), self::READ_CONFIG_SCHEMA);
    }

    #[\Testo\Test]
    public function testProviderFromArray(): void
    {
        $this->prepareContainer(['provider' => new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);
        $newSchema = self::ANOTHER_SCHEMA;

        $provider = $this->createSchemaProvider(['provider' => $newSchema]);

        \Testo\Assert::same($provider->read(), $newSchema);
    }

    #[\Testo\Test]
    public function testIgnoreStringKeyIfDefinitionIsNotArray(): void
    {
        $this->prepareContainer(['provider' => new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);

        $provider = $this->createSchemaProvider(['provider' => new ArraySchemaProvider(self::ANOTHER_SCHEMA)]);

        \Testo\Assert::same($provider->read(), self::ANOTHER_SCHEMA);
    }

    #[\Testo\Test]
    public function testProviderAsObject(): void
    {
        $provider = $this->createSchemaProvider([new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);

        \Testo\Assert::same($provider->read(), self::READ_CONFIG_SCHEMA);
    }

    #[\Testo\Test]
    public function testProviderAsBadClassObject(): void
    {
        $provider = $this->createSchemaProvider(['BadSchemaProvider']);

        \Testo\Expect::exception(BadDeclarationException::class);

        $provider->read();
    }

    // Clear test

    #[\Testo\Test]
    public function testClearWithoutProviders(): void
    {
        $provider = $this->createSchemaProvider([]);

        $result = $provider->clear();

        // exception was not thrown
        \Testo\Assert::false($result);
    }

    #[\Testo\Test]
    public function testClearNotConfigured(): void
    {
        $provider = $this->createSchemaProvider(null);

        \Testo\Expect::exception(SchemaProviderException::class);

        $provider->clear();
    }

    #[\Testo\Test]
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
        \Testo\Assert::false($result);
    }

    #[\Testo\Test]
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
        \Testo\Assert::true($result);
    }

    #[\Testo\Test]
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

        \Testo\Assert::null($writeable->read());
        \Testo\Assert::null($notWriteable1->read());
        \Testo\Assert::null($origin->read());
        \Testo\Assert::same($notWriteable2->read(), self::ANOTHER_SCHEMA);
    }

    #[\Testo\Test]
    public function testClearWithException(): void
    {
        $provider1 = (new SameOriginProvider(self::READ_CONFIG_SCHEMA))
            ->withConfig([SameOriginProvider::EXCEPTION_ON_CLEAR => true]);
        $provider2 = new ArraySchemaProvider(self::ANOTHER_SCHEMA);
        $provider3 = new ArraySchemaProvider(self::ANOTHER_SCHEMA);
        $provider = $this->createSchemaProvider([$provider1, $provider2, $provider3]);

        \Testo\Expect::exception(CumulativeException::class);

        try {
            $provider->clear();
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // first provider throws exception
            \Testo\Assert::same($provider1->read(), self::READ_CONFIG_SCHEMA);
            // next provider will be cleared
            \Testo\Assert::null($provider2->read());
            // last provider will be cleared
            \Testo\Assert::null($provider3->read());
        }
    }

    // Reading test

    #[\Testo\Test]
    public function testReadNotConfigured(): void
    {
        $provider = $this->createSchemaProvider(null);

        \Testo\Expect::exception(SchemaProviderException::class);

        $provider->read();
    }

    #[\Testo\Test]
    public function testReadWithoutProviders(): void
    {
        $provider = $this->createSchemaProvider([]);

        \Testo\Assert::null($provider->read());
    }

    #[\Testo\Test]
    public function testReadWithException(): void
    {
        $this->prepareContainer([
            'withoutSchema' => (new SameOriginProvider(self::READ_CONFIG_SCHEMA))
                ->withConfig([SameOriginProvider::EXCEPTION_ON_READ => true]),
        ]);

        \Testo\Expect::exception(SchemaProviderException::class);

        $this->createSchemaProvider(['withoutSchema'])->read();
    }

    #[\Testo\Test]
    public function testReadFromOneProvider(): void
    {
        $provider = $this->createSchemaProvider([new ArraySchemaProvider(self::READ_CONFIG_SCHEMA)]);

        $schema = $provider->read();

        \Testo\Assert::same($schema, self::READ_CONFIG_SCHEMA);
    }

    #[\Testo\Test]
    public function testReadingOrderWithNullIgnoring(): void
    {
        $this->prepareContainer([
            'withSchema' => new ArraySchemaProvider(self::READ_CONFIG_SCHEMA),
            'withoutSchema' => new ArraySchemaProvider(null),
        ]);

        $provider = $this->createSchemaProvider(['withoutSchema', 'withSchema']);

        \Testo\Assert::notNull($provider->read());
    }

    #[\Testo\Test]
    public function testReadWithAlternativeProvider(): void
    {
        $provider = $this->createSchemaProvider([
            new ArraySchemaProvider(),
            new ArraySchemaProvider(),
            new ArraySchemaProvider(),
        ]);

        $schema1 = $provider->read();
        $schema2 = $provider->read(new ArraySchemaProvider(self::READ_CONFIG_SCHEMA));

        \Testo\Assert::null($schema1);
        \Testo\Assert::same($schema2, self::READ_CONFIG_SCHEMA);
    }

    #[\Testo\Test]
    public function testReadWithoutProvidersButWithAlternativeProvider(): void
    {
        $provider = $this->createSchemaProvider();

        $schema2 = $provider->read(new ArraySchemaProvider(self::READ_CONFIG_SCHEMA));

        \Testo\Assert::same($schema2, self::READ_CONFIG_SCHEMA);
    }

    #[\Testo\Lifecycle\BeforeTest]
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
