<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\ToOneRelationshipUpdatability;
use EDT\Wrapping\Properties\ToOneRelationshipReadability;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 * @template TRelationshipType of ResourceTypeInterface<TCondition, TSorting, TRelationship>
 *
 * @template-extends AbstractConfig<TCondition, TEntity, ToOneRelationshipReadability<TCondition, TSorting, TEntity, TRelationship, TRelationshipType>, ToOneRelationshipUpdatability<TCondition, TSorting, TEntity, TRelationship, TRelationshipType>>
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

    /**
     * @param list<TCondition>                                 $entityConditions
     * @param list<TCondition>                                 $valueConditions
     * @param null|callable(TEntity, TRelationship|null): void $customWrite
     *
     * @return $this
     */
    public function enableUpdatability(
        array $entityConditions = [],
        array $valueConditions = [],
        callable $customWrite = null
    ): ToOneRelationshipConfig {
        $this->assertNullOrImplements(TransferableTypeInterface::class, 'readable');

        $this->updatability = new ToOneRelationshipUpdatability(
            $entityConditions,
            $valueConditions,
            $this->relationshipType,
            $customWrite
        );

        return $this;
    }

    protected function getType(): TypeInterface
    {
        return $this->type;
    }
}
