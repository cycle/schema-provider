<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Unit\Path;

use Cycle\Schema\Provider\Path\SimpleResolver;
use PHPUnit\Framework\TestCase;

final class SimpleResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new SimpleResolver();

        $this->assertSame('foo/bar', $resolver->resolve('foo/bar'));
    }
}
