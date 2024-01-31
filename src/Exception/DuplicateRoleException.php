<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Exception;

final class DuplicateRoleException extends \LogicException
{
    public function __construct(string $role)
    {
        parent::__construct(\sprintf('The `%s` role already exists in the DB schema.', $role));
    }
}
