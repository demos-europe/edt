<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorParameterInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipReadabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToOneRelationshipConfigInterface<TCondition, TSorting, TEntity, TRelationship>
 */
class DtoToOneRelationshipConfig implements ToOneRelationshipConfigInterface
{
    /**
     * @param ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>|null $readability
     * @param RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>|null $updatability
     * @param PropertySetabilityInterface<TEntity>|null $postInstantiability
     * @param PropertyLinkInterface|null $filterLink
     * @param PropertyLinkInterface|null $sortLink
     */
    public function __construct(
        protected readonly ?ToOneRelationshipReadabilityInterface $readability,
        protected readonly ?PropertySetabilityInterface $updatability,
        protected readonly ?PropertySetabilityInterface $postInstantiability,
        protected readonly ?ConstructorParameterInterface $instantiability,
        protected readonly ?PropertyLinkInterface $filterLink,
        protected readonly ?PropertyLinkInterface $sortLink
    ) {}

    public function getReadability(): ?ToOneRelationshipReadabilityInterface
    {
        return $this->readability;
    }

    public function getUpdatability(): ?RelationshipSetabilityInterface
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
