<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Path;

interface ResolverInterface
{
    public function resolve(string $path): string;
}
