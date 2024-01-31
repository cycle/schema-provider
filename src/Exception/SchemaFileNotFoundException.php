<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Exception;

final class SchemaFileNotFoundException extends \RuntimeException
{
    public function __construct(string $file)
    {
        parent::__construct(\sprintf('Schema file `%s` not found.', $file));
    }
}
