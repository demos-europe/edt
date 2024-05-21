<?php

declare(strict_types=1);

namespace EDT\Querying\ObjectProviders;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

/**
 * @template TEntity of object
 *
 * @template-extends PrefilledEntityProvider<TEntity>
 */
class MutableEntityProvider extends PrefilledEntityProvider
{
    /**
     * @param list<TEntity> $entities
     */
    public function __construct(ConditionEvaluator $conditionEvaluator, Sorter $sorter, array $entities = [])
    {
        parent::__construct($conditionEvaluator, $sorter, $entities);
    }

    /**
     * Add the given entity to this provider.
     *
     * No check is done if the entity already exists in this provider. I.e. adding the same entity multiple times may
     * result in it being returned multiple times via {@link getEntities()}.
     *
     * @param TEntity $entity
     */
    public function addEntity(object $entity): void
    {
        $this->entities[] = $entity;
    }

    /**
     * Remove all entities that match all the given conditions each.
     *
     * @param list<FunctionInterface<bool>> $conditions
     * @param positive-int|null $expectedCount if non-`null`, execute the removal only if exactly this number of
     * entities match the given conditions and thrown an exception if not
     *
     * @return int<0,max>
     */
    public function removeEntities(array $conditions, int $expectedCount = null): int
    {
        $newList = array_values(array_filter(
            $this->entities,
            fn(object $entity): bool => !$this->conditionEvaluator->evaluateConditions($entity, $conditions)
        ));

        $removalCount = count($this->entities) - count($newList);
        Assert::greaterThanEq($removalCount, 0);
        if (null !== $expectedCount && $removalCount !== $expectedCount) {
            throw new InvalidArgumentException("Expected $expectedCount entities to be removed by matching the given conditions, found $removalCount matching entities instead.");
        }
        $this->entities = $newList;

        return $removalCount;
    }
}
