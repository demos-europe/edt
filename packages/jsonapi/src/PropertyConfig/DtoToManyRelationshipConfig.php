<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipReadabilityInterface;

/**
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToManyRelationshipConfigInterface<TEntity, TRelationship>
 */
class DtoToManyRelationshipConfig implements ToManyRelationshipConfigInterface
{
    /**
     * @param ToManyRelationshipReadabilityInterface<TEntity, TRelationship>|null $readability
     * @param list<RelationshipSetBehaviorInterface<TEntity, TRelationship>> $updateBehaviors
     * @param list<PropertySetBehaviorInterface<TEntity>> $postConstructorBehaviors
     * @param list<ConstructorBehaviorInterface> $constructorBehaviors
     */
    public function __construct(
        protected readonly ?ToManyRelationshipReadabilityInterface $readability,
        protected readonly array $updateBehaviors,
        protected readonly array $postConstructorBehaviors,
        protected readonly array $constructorBehaviors,
        protected readonly ?PropertyLinkInterface $filterLink,
        protected readonly ?PropertyLinkInterface $sortLink
    ) {}

    public function getReadability(): ?ToManyRelationshipReadabilityInterface
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
