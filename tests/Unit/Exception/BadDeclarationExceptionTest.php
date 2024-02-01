<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit\Exception;

use Cycle\Schema\Provider\Exception\BadDeclarationException;
use PHPUnit\Framework\TestCase;

final class BadDeclarationExceptionTest extends TestCase
{
    private const DEFAULT_CLASS = \stdClass::class;
    private const DEFAULT_PARAMETER = 'Default parameter';
    private const DEFAULT_MESSAGE_PATTERN = '/Default parameter should be instance of stdClass or its declaration\\./';
    private const RECEIVED_PATTERN = '/%s was received instead\\./';

    protected function prepareException(
        mixed $argument,
        string $parameter = self::DEFAULT_PARAMETER,
        string $class = self::DEFAULT_CLASS
    ): BadDeclarationException {
        return new BadDeclarationException($parameter, $class, $argument);
    }

    /**
     * @dataProvider argumentValueProvider
     */
    public function testTypeMessage(mixed $value, string $message): void
    {
        $exception = $this->prepareException($value);
        $pattern = sprintf(self::RECEIVED_PATTERN, $message);

        $this->assertMatchesRegularExpression($pattern, $exception->getMessage());
    }

    public function testDefaultState(): void
    {
        $exception = $this->prepareException(null);

        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertSame(0, $exception->getCode());
        $this->assertMatchesRegularExpression(self::DEFAULT_MESSAGE_PATTERN, $exception->getMessage());
    }

    public static function argumentValueProvider(): \Traversable
    {
        yield [null, 'Null'];
        yield [42, 'Int'];
        yield [new \DateTimeImmutable(), 'Instance of DateTimeImmutable'];
        yield [STDIN, 'Resource \\(stream\\)'];
        yield [[], 'Array'];
    }
}
