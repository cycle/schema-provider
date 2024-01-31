<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Path;

final class SimpleResolver implements ResolverInterface
{
    public function resolve(string $path): string
    {
        return $path;
    }
}
