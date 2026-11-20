<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit\Exception;

use Cycle\Schema\Provider\Exception\BadDeclarationException;
use PHPUnit\Framework\TestCase;

final class BadDeclarationExceptionTest
{
    private const DEFAULT_CLASS = \stdClass::class;
    private const DEFAULT_PARAMETER = 'Default parameter';
    private const DEFAULT_MESSAGE_PATTERN = '/Default parameter should be instance of stdClass or its declaration\\./';
    private const RECEIVED_PATTERN = '/%s was received instead\\./';

    public static function argumentValueProvider(): \Traversable
    {
        yield [null, 'Null'];
        yield [42, 'Int'];
        yield [new \DateTimeImmutable(), 'Instance of DateTimeImmutable'];
        yield [STDIN, 'Resource \\(stream\\)'];
        yield [[], 'Array'];
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('argumentValueProvider')]
    public function testTypeMessage(mixed $value, string $message): void
    {
        $exception = $this->prepareException($value);
        $pattern = sprintf(self::RECEIVED_PATTERN, $message);

        \Testo\Assert::string($exception->getMessage())->matchesRegex($pattern);
    }

    #[\Testo\Test]
    public function testDefaultState(): void
    {
        $exception = $this->prepareException(null);

        \Testo\Assert::instanceOf($exception, \Throwable::class);
        \Testo\Assert::same($exception->getCode(), 0);
        \Testo\Assert::string($exception->getMessage())->matchesRegex(self::DEFAULT_MESSAGE_PATTERN);
    }

    protected function prepareException(
        mixed $argument,
        string $parameter = self::DEFAULT_PARAMETER,
        string $class = self::DEFAULT_CLASS,
    ): BadDeclarationException {
        return new BadDeclarationException($parameter, $class, $argument);
    }
}
