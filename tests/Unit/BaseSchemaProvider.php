<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Provider\SchemaProviderInterface;
use Cycle\Schema\Provider\Tests\Fixtures\ArraySchemaProvider;
use Testo\Assert;
use Testo\Test;

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

    #[Test]
    public function testWithConfigImmutability(): void
    {
        $schemaProvider1 = $this->createSchemaProvider();
        $schemaProvider2 = $schemaProvider1->withConfig(static::READ_CONFIG);

        Assert::same($schemaProvider1->read(), static::DEFAULT_SCHEMA);
        Assert::same($schemaProvider2->read(), static::READ_CONFIG_SCHEMA);
        Assert::notSame($schemaProvider2, $schemaProvider1);
    }

    #[Test]
    public function testReadFromNextProvider(): void
    {
        $provider1 = $this->createSchemaProvider();
        $provider2 = new ArraySchemaProvider(static::READ_CONFIG_SCHEMA);

        $result = $provider1->read($provider2);

        Assert::same($result, static::READ_CONFIG_SCHEMA);
    }

    abstract protected function createSchemaProvider(?array $config = null): SchemaProviderInterface;
}
