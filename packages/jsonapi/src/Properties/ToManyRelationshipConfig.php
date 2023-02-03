<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\ToManyRelationshipUpdatability;
use EDT\Wrapping\Properties\ToManyRelationshipReadability;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends AbstractConfig<TCondition, TEntity, ToManyRelationshipReadability<TCondition, TSorting, TEntity, TRelationship>, ToManyRelationshipUpdatability<TCondition, TSorting, TEntity, TRelationship>>
 */
class ToManyRelationshipConfig extends AbstractConfig
{
    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TEntity> $type
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     */
    public function __construct(
        protected readonly ResourceTypeInterface $type,
        private readonly ResourceTypeInterface $relationshipType
    ) {}

    /**
     * @return ResourceTypeInterface<TCondition, TSorting, TRelationship>
     */
    public function getRelationshipType(): ResourceTypeInterface
    {
        return $this->relationshipType;
    }

    /**
     * @param null|callable(TEntity): iterable<TRelationship> $customRead
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

        $this->readability = new ToManyRelationshipReadability(
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
     * @param null|callable(TEntity, iterable<TRelationship>): void $customWrite
     *
     * @return $this
     */
    public function enableUpdatability(
        array $entityConditions = [],
        array $valueConditions = [],
        callable $customWrite = null
    ): ToManyRelationshipConfig {
        $this->assertNullOrImplements(TransferableTypeInterface::class, 'readable');

        $this->updatability = new ToManyRelationshipUpdatability(
            $entityConditions,
            $valueConditions,
            $this->relationshipType,
            $customWrite
        );

        return $this;
    }

    /**
     * @return ResourceTypeInterface<TCondition, TSorting, TEntity>
     */
    protected function getType(): ResourceTypeInterface
    {
        return $this->type;
    }
}
