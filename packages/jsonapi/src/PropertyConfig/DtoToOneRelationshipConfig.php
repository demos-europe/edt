<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipReadabilityInterface;

/**
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToOneRelationshipConfigInterface<TEntity, TRelationship>
 */
class DtoToOneRelationshipConfig implements ToOneRelationshipConfigInterface
{
    /**
     * @param ToOneRelationshipReadabilityInterface<TEntity, TRelationship>|null $readability
     * @param list<RelationshipSetBehaviorInterface<TEntity, TRelationship>> $updateBehaviors
     * @param list<PropertySetBehaviorInterface<TEntity>> $postConstructorBehaviors
     * @param list<ConstructorBehaviorInterface> $constructorBehaviors
     * @param PropertyLinkInterface|null $filterLink
     * @param PropertyLinkInterface|null $sortLink
     */
    public function __construct(
        protected readonly ?ToOneRelationshipReadabilityInterface $readability,
        protected readonly array $updateBehaviors,
        protected readonly array $postConstructorBehaviors,
        protected readonly array $constructorBehaviors,
        protected readonly ?PropertyLinkInterface $filterLink,
        protected readonly ?PropertyLinkInterface $sortLink
    ) {}

    public function getReadability(): ?ToOneRelationshipReadabilityInterface
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
