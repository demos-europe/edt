<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierPostConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierReadabilityInterface;

/**
 * @template TEntity of object
 *
 * @template-implements IdentifierConfigInterface<TEntity>
 */
class DtoIdentifierConfig implements IdentifierConfigInterface
{
    /**
     * @param IdentifierReadabilityInterface<TEntity> $readability
     * @param list<IdentifierPostConstructorBehaviorInterface<TEntity>> $postConstructorBehaviors
     * @param list<ConstructorBehaviorInterface> $constructorBehaviors
     * @param PropertyLinkInterface|null $filterLink
     * @param PropertyLinkInterface|null $sortLink
     */
    public function __construct(
        protected readonly IdentifierReadabilityInterface $readability,
        protected readonly array $postConstructorBehaviors,
        protected readonly array $constructorBehaviors,
        protected readonly ?PropertyLinkInterface $filterLink,
        protected readonly ?PropertyLinkInterface $sortLink
    ) {}

    public function getReadability(): IdentifierReadabilityInterface
    {
        return $this->readability;
    }

    public function getPostConstructorBehaviors(): array
    {
        return $this->postConstructorBehaviors;
    }

    public function getConstructorBehaviors(): array
    {
        return $this->constructorBehaviors;
    }

    public function getFilterLink(): ?PropertyLinkInterface
    {
        return $this->filterLink;
    }

    public function getSortLink(): ?PropertyLinkInterface
    {
        return $this->sortLink;
    }
}
