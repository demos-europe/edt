<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends Updatability<TCondition>
 */
class RelationshipUpdatability extends Updatability
{
    /**
     * @var class-string<TEntity>
     */
    private string $relationshipEntityClass;

    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $valueConditions
     * @param class-string<TEntity> $relationshipEntityClass
     */
    public function __construct(
        array $entityConditions,
        array $valueConditions,
        string $relationshipEntityClass
    ) {
        parent::__construct($entityConditions, $valueConditions);
        $this->relationshipEntityClass = $relationshipEntityClass;
    }

    /**
     * @return class-string<TEntity>
     */
    public function getRelationshipEntityClass(): string
    {
        return $this->relationshipEntityClass;
    }
}
