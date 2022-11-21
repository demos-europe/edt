<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\RelationshipUpdatability;
use EDT\Wrapping\Properties\ToOneRelationshipReadability;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 * @template TRelationshipType of ResourceTypeInterface<TCondition, TSorting, TRelationship>
 *
 * @template-extends AbstractConfig<TCondition, TEntity, ToOneRelationshipReadability<TCondition, TSorting, TEntity, TRelationship, TRelationshipType>, RelationshipUpdatability<TCondition, TRelationship>>
 */
class ToOneRelationshipConfig extends AbstractConfig
{
    /**
     * @var TRelationshipType
     */
    private ResourceTypeInterface $relationshipType;

    /**
     * @var ResourceTypeInterface<TCondition, TSorting, TEntity>
     */
    protected ResourceTypeInterface $type;

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TEntity> $type
     * @param TRelationshipType $relationshipType
     */
    public function __construct(ResourceTypeInterface $type, ResourceTypeInterface $relationshipType)
    {
        $this->type = $type;
        $this->relationshipType = $relationshipType;
    }

    /**
     * @return TRelationshipType
     */
    public function getRelationshipType(): ResourceTypeInterface
    {
        return $this->relationshipType;
    }

    /**
     * @param null|callable(TEntity): (TRelationship|null) $customRead
     *
     * @return $this
     *
     * @throws ResourcePropertyConfigException
     */
    public function enableReadability(
        bool $defaultField = false,
        bool $defaultInclude = false,
        callable $customRead = null,
        bool $allowingInconsistencies = false
    ): self {
        $this->assertNullOrImplements(TransferableTypeInterface::class, 'readable');

        $this->readability = new ToOneRelationshipReadability(
            $defaultField,
            $allowingInconsistencies,
            $defaultInclude,
            $customRead,
            $this->relationshipType
        );

        return $this;
    }

    protected function createUpdatability(array $entityConditions, array $valueConditions): RelationshipUpdatability
    {
        return new RelationshipUpdatability($entityConditions, $valueConditions, $this->relationshipType->getEntityClass());
    }

    protected function getType(): ?TypeInterface
    {
        return $this->relationshipType;
    }
}
