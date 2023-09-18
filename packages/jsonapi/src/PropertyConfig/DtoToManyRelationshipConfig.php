<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorParameterInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipReadabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToManyRelationshipConfigInterface<TCondition, TSorting, TEntity, TRelationship>
 */
class DtoToManyRelationshipConfig implements ToManyRelationshipConfigInterface
{
    /**
     * @param ToManyRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>|null $readability
     * @param RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>|null $updatability
     * @param PropertySetabilityInterface<TEntity>|null $postInstantiability
     */
    public function __construct(
        protected readonly ?ToManyRelationshipReadabilityInterface $readability,
        protected readonly ?PropertySetabilityInterface $updatability,
        protected readonly ?PropertySetabilityInterface $postInstantiability,
        protected readonly ?ConstructorParameterInterface $instantiability,
        protected readonly ?PropertyLinkInterface $filterLink,
        protected readonly ?PropertyLinkInterface $sortLink
    ) {}

    public function getReadability(): ?ToManyRelationshipReadabilityInterface
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
