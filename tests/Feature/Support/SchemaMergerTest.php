<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Feature\Support;

use Cycle\Schema\Provider\Exception\DuplicateRoleException;
use Cycle\Schema\Provider\Support\SchemaMerger;
use PHPUnit\Framework\TestCase;

final class SchemaMergerTest
{
    #[\Testo\Test]
    public function testMergeNulls(): void
    {
        $result = $this->createMerger()->merge(null, null);

        \Testo\Assert::null($result);
    }

    #[\Testo\Test]
    public function testMergeNullAndEmptyArray(): void
    {
        $result = $this->createMerger()->merge(null, []);

        \Testo\Assert::same($result, []);
    }

    #[\Testo\Test]
    public function testMergeDifferentRoles(): void
    {
        $result = $this->createMerger()->merge(['user' => []], ['post' => []], []);

        \Testo\Assert::same($result, ['user' => [], 'post' => []]);
    }

    #[\Testo\Test]
    public function testMergeSameRoles(): void
    {
        $result = $this->createMerger()->merge(['user' => []], ['post' => [], 'user' => [], 'tag' => []]);

        \Testo\Assert::same($result, ['user' => [], 'post' => [], 'tag' => []]);
    }

    #[\Testo\Test]
    public function testMergeConflictRoles(): void
    {
        \Testo\Expect::exception(DuplicateRoleException::class);

        $this->createMerger()->merge(['user' => []], ['post' => [], 'user' => ['']]);
    }

    #[\Testo\Test]
    public function testMergeNumericKeys(): void
    {
        $result = $this->createMerger()->merge([['foo'], ['bar']], [['bar'], ['baz']]);

        \Testo\Assert::same($result, [['foo'], ['bar'], ['bar'], ['baz']]);
    }

    private function createMerger(): SchemaMerger
    {
        return new SchemaMerger();
    }
}
