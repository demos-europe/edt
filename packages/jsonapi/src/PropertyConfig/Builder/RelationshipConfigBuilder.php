<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Querying\PropertyPaths\RelationshipLink;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TRelationship of object
 */
abstract class RelationshipConfigBuilder extends AbstractPropertyConfigBuilder
{
    /**
     * @var ResourceTypeInterface<TCondition, TSorting, TRelationship>|null
     */
    protected ?ResourceTypeInterface $relationshipType = null;

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     *
     * @return static
     */
    public function setRelationshipType(ResourceTypeInterface $relationshipType): self
    {
        $this->relationshipType = $relationshipType;

        return $this;
    }

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     */
    protected function getFilterLink(ResourceTypeInterface $relationshipType): ?PropertyLinkInterface
    {
        if (!$this->filterable) {
            return null;
        }

        if ($this->isExposedType()) {
            return null;
        }

        return new RelationshipLink(
            $this->getPropertyPath(),
            static fn (): array => $relationshipType->getFilteringProperties()
        );
    }

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     */
    protected function getSortLink(ResourceTypeInterface $relationshipType): ?PropertyLinkInterface
    {
        if (!$this->sortable) {
            return null;
        }

        if ($this->isExposedType()) {
            return null;
        }

        return new RelationshipLink(
            $this->getPropertyPath(),
            static fn (): array => $relationshipType->getSortingProperties()
        );
    }

    /**
     * Even if a relationship property was defined in a type, we do not allow its usage if the
     * target type of the relationship is not set as exposed.
     */
    protected function isExposedType(): bool
    {
        return $this->relationshipType instanceof ExposableRelationshipTypeInterface
            && $this->relationshipType->isExposedAsRelationship();
    }
}
