<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Feature;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Provider\Exception\DuplicateRoleException;
use Cycle\Schema\Provider\MergeSchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Stub\ArraySchemaProvider;
use Cycle\Schema\Provider\Tests\Feature\Support\BaseProviderCollector;

final class MergeSchemaProviderTest extends BaseProviderCollector
{
    protected const READ_CONFIG = [
        [ArraySchemaProvider::class, self::SCHEMA_PART_1],
        [ArraySchemaProvider::class, self::SCHEMA_PART_2],
    ];
    protected const READ_CONFIG_SCHEMA = self::SCHEMA_PART_1 + self::SCHEMA_PART_2;
    protected const SCHEMA_PART_1 = parent::READ_CONFIG_SCHEMA;
    protected const SCHEMA_PART_2 = [
        'post' => [
            Schema::ENTITY => \stdClass::class,
            Schema::MAPPER => \stdClass::class,
        ],
    ];
    protected const SCHEMA_CONFLICT = [
        'post' => [
            Schema::ENTITY => \stdClass::class,
            Schema::DATABASE => 'default',
        ],
    ];

    protected function createSchemaProvider(?array $config = []): MergeSchemaProvider
    {
        $provider = new MergeSchemaProvider($this->container);
        return $config === null ? $provider : $provider->withConfig($config);
    }

    public function testMergeSameValueConflict(): void
    {
        $provider = $this->createSchemaProvider([...self::READ_CONFIG, new ArraySchemaProvider(self::SCHEMA_PART_1)]);
        $schema = $provider->read();
        self::assertSame(self::READ_CONFIG_SCHEMA, $schema);
    }

    public function testMergeDifferentValueConflict(): void
    {
        $this->expectException(DuplicateRoleException::class);

        $provider = $this->createSchemaProvider([...self::READ_CONFIG, new ArraySchemaProvider(self::SCHEMA_CONFLICT)]);
        $provider->read();
    }
}
