<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityInterface;

/**
 * @template TEntity of object
 *
 * @template-implements AttributeConfigInterface<TEntity>
 */
class DtoAttributeConfig implements AttributeConfigInterface
{
    /**
     * @param AttributeReadabilityInterface<TEntity>|null $readability
     * @param list<PropertyUpdatabilityInterface<TEntity>> $updateBehaviors
     * @param list<PropertySetBehaviorInterface<TEntity>> $postConstructorBehaviors
     * @param list<ConstructorBehaviorInterface> $constructorBehaviors
     */
    public function __construct(
        protected readonly ?AttributeReadabilityInterface $readability,
        protected readonly array $updateBehaviors,
        protected readonly array $postConstructorBehaviors,
        protected readonly array $constructorBehaviors,
        protected readonly ?PropertyLinkInterface $filterLink,
        protected readonly ?PropertyLinkInterface $sortLink
    ) {}

    public function getReadability(): ?AttributeReadabilityInterface
    {
        return $this->readability;
    }

    public function getUpdateBehaviors(): array
    {
        return $this->updateBehaviors;
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
