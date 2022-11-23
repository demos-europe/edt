<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 */
abstract class AbstractUpdatability
{
    /**
     * @var list<TCondition>
     */
    private array $entityConditions;

    /**
     * @var list<TCondition>
     */
    private array $valueConditions;

    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $valueConditions
     */
    public function __construct(array $entityConditions, array $valueConditions)
    {
        $this->entityConditions = $entityConditions;
        $this->valueConditions = $valueConditions;
    }

    /**
     * The entity to update with some value must match these conditions.
     *
     * @return list<TCondition>
     */
    public function getEntityConditions(): array
    {
        return $this->entityConditions;
    }

    /**
     * The conditions that will be used to determine if the corresponding property
     * can be updated with the value to set into the property.
     *
     * I.e. the conditions will be evaluated against the value to set, not the entity to update.
     *
     * @return list<TCondition>
     */
    public function getValueConditions(): array
    {
        return $this->valueConditions;
    }
}
