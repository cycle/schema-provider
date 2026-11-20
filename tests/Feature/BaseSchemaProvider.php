<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Feature;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Provider\SchemaProviderInterface;
use Cycle\Schema\Provider\Tests\Feature\Stub\ArraySchemaProvider;
use PHPUnit\Framework\TestCase;

abstract class BaseSchemaProvider
{
    protected const READ_CONFIG = [];
    protected const READ_CONFIG_SCHEMA = [
        'user' => [
            Schema::ENTITY => \stdClass::class,
            Schema::MAPPER => \stdClass::class,
            Schema::DATABASE => 'default',
            Schema::TABLE => 'user',
            Schema::PRIMARY_KEY => 'id',
            Schema::COLUMNS => [
                'id' => 'id',
                'email' => 'email',
                'balance' => 'balance',
            ],
            Schema::TYPECAST => [
                'id' => 'int',
                'balance' => 'float',
            ],
            Schema::RELATIONS => [],
        ],
    ];
    protected const DEFAULT_SCHEMA = null;

    #[\Testo\Test]
    public function testWithConfigImmutability(): void
    {
        $schemaProvider1 = $this->createSchemaProvider();
        $schemaProvider2 = $schemaProvider1->withConfig(static::READ_CONFIG);

        \Testo\Assert::same($schemaProvider1->read(), static::DEFAULT_SCHEMA);
        \Testo\Assert::same($schemaProvider2->read(), static::READ_CONFIG_SCHEMA);
        \Testo\Assert::notSame($schemaProvider2, $schemaProvider1);
    }

    #[\Testo\Test]
    public function testReadFromNextProvider(): void
    {
        $provider1 = $this->createSchemaProvider();
        $provider2 = new ArraySchemaProvider(static::READ_CONFIG_SCHEMA);

        $result = $provider1->read($provider2);

        \Testo\Assert::same($result, static::READ_CONFIG_SCHEMA);
    }

    abstract protected function createSchemaProvider(?array $config = null): SchemaProviderInterface;
}
