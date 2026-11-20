<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit\Support;

use Cycle\Schema\Provider\Exception\DuplicateRoleException;
use Cycle\Schema\Provider\Support\SchemaMerger;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class SchemaMergerTest
{
    public function testMergeNulls(): void
    {
        $result = $this->createMerger()->merge(null, null);

        Assert::null($result);
    }

    public function testMergeNullAndEmptyArray(): void
    {
        $result = $this->createMerger()->merge(null, []);

        Assert::same($result, []);
    }

    public function testMergeDifferentRoles(): void
    {
        $result = $this->createMerger()->merge(['user' => []], ['post' => []], []);

        Assert::same($result, ['user' => [], 'post' => []]);
    }

    public function testMergeSameRoles(): void
    {
        $result = $this->createMerger()->merge(['user' => []], ['post' => [], 'user' => [], 'tag' => []]);

        Assert::same($result, ['user' => [], 'post' => [], 'tag' => []]);
    }

    #[ExpectException(DuplicateRoleException::class)]
    public function testMergeConflictRoles(): void
    {
        $this->createMerger()->merge(['user' => []], ['post' => [], 'user' => ['']]);
    }

    public function testMergeNumericKeys(): void
    {
        $result = $this->createMerger()->merge([['foo'], ['bar']], [['bar'], ['baz']]);

        Assert::same($result, [['foo'], ['bar'], ['bar'], ['baz']]);
    }

    private function createMerger(): SchemaMerger
    {
        return new SchemaMerger();
    }
}
