<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit\Exception;

use Cycle\Schema\Provider\Exception\CumulativeException;
use PHPUnit\Framework\TestCase;

final class CumulativeExceptionTest
{
    #[\Testo\Test]
    public function testDefaultState(): void
    {
        $exception = $this->prepareException();

        \Testo\Assert::instanceOf($exception, \Throwable::class);
        \Testo\Assert::same($exception->getMessage(), '0 exceptions were thrown.');
        \Testo\Assert::same($exception->getCode(), 0);
    }

    #[\Testo\Test]
    public function testGetExceptions(): void
    {
        $list = [
            new \RuntimeException(),
            new \Exception(),
            new \InvalidArgumentException(),
        ];

        $exception = $this->prepareException(...$list);

        \Testo\Assert::same($exception->getExceptions(), $list);
    }

    #[\Testo\Test]
    public function testGetMessageWithOneException(): void
    {
        $list = [
            new \RuntimeException('Foo message.', 42),
        ];

        $exception = $this->prepareException(...$list);

        \Testo\Assert::int(strpos($exception->getMessage(), '[RuntimeException] #42: Foo message.'));
        \Testo\Assert::string($exception->getMessage())->matchesRegex('/One exception was thrown\\./');
    }

    #[\Testo\Test]
    public function testGetMessageWithMultipleExceptions(): void
    {
        $list = [
            new \RuntimeException('Foo message.', 42),
            new \Exception('Bar message.'),
            new \InvalidArgumentException('Baz message.'),
        ];

        $exception = $this->prepareException(...$list);

        \Testo\Assert::string($exception->getMessage())->matchesRegex('/3 exceptions were thrown\\./');

        \Testo\Assert::string($exception->getMessage())->matchesRegex('/\\n1\\) [^\\n]++\\n\\[RuntimeException\\] \\#42\\: Foo message\\./');
        \Testo\Assert::string($exception->getMessage())->matchesRegex('/\\n2\\) [^\\n]++\\n\\[Exception] #0: Bar message./');
        \Testo\Assert::string($exception->getMessage())->matchesRegex('/\\n3\\) [^\\n]++\\n\\[InvalidArgumentException] #0: Baz message./');
    }

    private function prepareException(\Throwable ...$exceptions): CumulativeException
    {
        return new CumulativeException(...$exceptions);
    }
}
