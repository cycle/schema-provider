<?php

declare(strict_types=1);

namespace Cycle\Schema\Provider\Support;

use Cycle\Schema\Provider\Exception\BadDeclarationException;
use Cycle\Schema\Provider\SchemaProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Auxiliary class for building scheme providers in a pipeline.
 */
final class DeferredSchemaProviderDecorator implements SchemaProviderInterface
{
    private SchemaProviderInterface|string $provider;
    private array $config = [];
    private ?self $nextProvider;
    private ?SchemaProviderInterface $latestProvider = null;
    private bool $resolved = false;
    private ContainerInterface $container;

    public function __construct(
        ContainerInterface $container,
        SchemaProviderInterface|string $provider,
        ?self $nextProvider
    ) {
        $this->provider = $provider;
        $this->container = $container;
        $this->nextProvider = $nextProvider;
    }

    /**
     * @throws BadDeclarationException
     */
    public function withConfig(array $config): self
    {
        $provider = !$this->resolved && count($this->config) === 0 ? $this->provider : $this->getProvider();
        $new = new self($this->container, $provider, $this->nextProvider);
        $new->config = $config;
        return $new;
    }

    /**
     * @throws BadDeclarationException
     */
    public function read(?SchemaProviderInterface $nextProvider = null): ?array
    {
        $nextProvider ??= $this->latestProvider;
        if ($nextProvider !== null && $this->nextProvider !== null) {
            $nextProvider = $this->nextProvider->withLatestProvider($nextProvider);
        } else {
            $nextProvider = $this->nextProvider ?? $nextProvider;
        }
        return $this->getProvider()->read($nextProvider);
    }

    public function clear(): bool
    {
        return $this->getProvider()->clear();
    }

    /**
     * @psalm-suppress InvalidReturnType,InvalidReturnStatement
     * @throws BadDeclarationException
     */
    private function getProvider(): SchemaProviderInterface
    {
        if ($this->resolved) {
            return $this->provider;
        }
        $provider = $this->provider;
        if (\is_string($provider)) {
            $provider = $this->container->get($provider);
        }
        if (!$provider instanceof SchemaProviderInterface) {
            throw new BadDeclarationException('Provider', SchemaProviderInterface::class, $provider);
        }
        $this->provider = \count($this->config) > 0 ? $provider->withConfig($this->config) : $provider;
        $this->resolved = true;
        return $this->provider;
    }

    /**
     * @throws BadDeclarationException
     */
    private function withLatestProvider(SchemaProviderInterface $provider): self
    {
        // resolve provider
        $this->getProvider();
        $new = clone $this;
        $new->latestProvider = $provider;
        return $new;
    }
}
