<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorParameterInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetabilityInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements AttributeConfigInterface<TCondition, TEntity>
 */
class DtoAttributeConfig implements AttributeConfigInterface
{
    /**
     * @param AttributeReadabilityInterface<TEntity>|null $readability
     * @param PropertyUpdatabilityInterface<TCondition, TEntity>|null $updatability
     * @param PropertySetabilityInterface<TEntity>|null $postInstantiability
     */
    public function __construct(
        protected readonly ?AttributeReadabilityInterface $readability,
        protected readonly ?PropertyUpdatabilityInterface $updatability,
        protected readonly ?PropertySetabilityInterface $postInstantiability,
        protected readonly ?ConstructorParameterInterface $instantiability,
        protected readonly ?PropertyLinkInterface $filterLink,
        protected readonly ?PropertyLinkInterface $sortLink
    ) {}

    public function getReadability(): ?AttributeReadabilityInterface
    {
        return $this->readability;
    }

    public function getUpdatability(): ?PropertyUpdatabilityInterface
    {
        return $this->updatability;
    }

    public function getPostInstantiability(): ?PropertySetabilityInterface
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
