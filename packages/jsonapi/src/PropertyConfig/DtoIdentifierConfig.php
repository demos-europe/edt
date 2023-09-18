<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorParameterInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierPostInstantiabilityInterface;

/**
 * @template TEntity of object
 *
 * @template-implements IdentifierConfigInterface<TEntity>
 */
class DtoIdentifierConfig implements IdentifierConfigInterface
{
    /**
     * @param IdentifierReadabilityInterface<TEntity> $readability
     * @param IdentifierPostInstantiabilityInterface<TEntity>|null $postInstantiability
     * @param PropertyLinkInterface|null $filterLink
     * @param PropertyLinkInterface|null $sortLink
     */
    public function __construct(
        protected readonly IdentifierReadabilityInterface          $readability,
        protected readonly ?IdentifierPostInstantiabilityInterface $postInstantiability,
        protected readonly ?ConstructorParameterInterface          $instantiability,
        protected readonly ?PropertyLinkInterface                  $filterLink,
        protected readonly ?PropertyLinkInterface                  $sortLink
    ) {}

    public function getReadability(): IdentifierReadabilityInterface
    {
        return $this->readability;
    }

    public function getPostInstantiability(): ?IdentifierPostInstantiabilityInterface
    {
        return $this->postInstantiability;
    }

    public function getInstantiability(): ?ConstructorParameterInterface
    {
        return $this->instantiability;
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
