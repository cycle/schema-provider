<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Tests\Feature\Stub;

final class SameOriginProvider extends ConfigurableSchemaProvider
{
    public function withConfig(array $config): self
    {
        $new = parent::withConfig($config);
        $new->schema = &$this->schema;
        return $new;
    }
}
