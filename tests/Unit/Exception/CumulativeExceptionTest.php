<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit\Exception;

use Cycle\Schema\Provider\Exception\CumulativeException;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CumulativeExceptionTest
{
    public function testDefaultState(): void
    {
        $exception = $this->prepareException();

        Assert::instanceOf($exception, \Throwable::class);
        Assert::same($exception->getMessage(), '0 exceptions were thrown.');
        Assert::same($exception->getCode(), 0);
    }

    public function testGetExceptions(): void
    {
        $list = [
            new \RuntimeException(),
            new \Exception(),
            new \InvalidArgumentException(),
        ];

        $exception = $this->prepareException(...$list);

        Assert::same($exception->getExceptions(), $list);
    }

    public function testGetMessageWithOneException(): void
    {
        $list = [
            new \RuntimeException('Foo message.', 42),
        ];

        $exception = $this->prepareException(...$list);

        Assert::int(strpos($exception->getMessage(), '[RuntimeException] #42: Foo message.'));
        Assert::string($exception->getMessage())->startsWith("One exception was thrown.\n\n1) ");
    }

    public function testGetMessageWithMultipleExceptions(): void
    {
        $list = [
            new \RuntimeException('Foo message.', 42),
            new \Exception('Bar message.'),
            new \InvalidArgumentException('Baz message.'),
        ];

        $exception = $this->prepareException(...$list);

        Assert::string($exception->getMessage())->matchesRegex('/3 exceptions were thrown\\./');

        Assert::string($exception->getMessage())->matchesRegex('/\\n1\\) [^\\n]++\\n\\[RuntimeException\\] \\#42\\: Foo message\\./');
        Assert::string($exception->getMessage())->matchesRegex('/\\n2\\) [^\\n]++\\n\\[Exception] #0: Bar message./');
        Assert::string($exception->getMessage())->matchesRegex('/\\n3\\) [^\\n]++\\n\\[InvalidArgumentException] #0: Baz message./');
    }

    private function prepareException(\Throwable ...$exceptions): CumulativeException
    {
        return new CumulativeException(...$exceptions);
    }
}
