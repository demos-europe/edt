<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\ObjectProviderInterface;
use EDT\Querying\Contracts\FluentQueryException;
use EDT\Querying\Contracts\SortMethodFactoryInterface;

/**
 * A query to retrieve objects of a specific type. Can be modified before being executed.
 *
 * Instances of this class "guide" the developer through the creation and execution of the
 * query to retrieve objects.
 *
 * You may want to implement a factory to create instances of this class instead of using
 * its constructor, to avoid manually providing the same parameters on every usage.
 *
 * @template T of object
 */
class FluentQuery
{
    /**
     * @var ObjectProviderInterface<T>
     */
    protected $objectProvider;
    /**
     * @var ConditionDefinition
     */
    private $conditionDefinition;
    /**
     * @var SliceDefinition
     */
    private $sliceDefinition;
    /**
     * @var SortDefinition
     */
    private $sortDefinition;

    /**
     * @param ObjectProviderInterface<T> $objectProvider
     */
    public function __construct(
        ObjectProviderInterface $objectProvider,
        ConditionDefinition $conditionDefinition,
        SortDefinition $sortDefinition,
        SliceDefinition $sliceDefinition
    ) {
        $this->objectProvider = $objectProvider;
        $this->conditionDefinition = $conditionDefinition;
        $this->sortDefinition = $sortDefinition;
        $this->sliceDefinition = $sliceDefinition;
    }

    /**
     * @param ObjectProviderInterface<T> $objectProvider
     */
    public static function createWithDefaultDefinitions(
        ConditionFactoryInterface $conditionFactory,
        SortMethodFactoryInterface $sortMethodFactory,
        ObjectProviderInterface $objectProvider
    ): self {
        return new self(
            $objectProvider,
            new ConditionDefinition($conditionFactory, true),
            new SortDefinition($sortMethodFactory),
            new SliceDefinition()
        );
    }

    /**
     * @return iterable<T>
     */
    public function getEntities(): iterable
    {
        return $this->objectProvider->getObjects(
            $this->conditionDefinition->getConditions(),
            $this->sortDefinition->getSortMethods(),
            $this->sliceDefinition->getOffset(),
            $this->sliceDefinition->getLimit()
        );
    }

    /**
     * @return T|null
     *
     * @throws FluentQueryException
     */
    public function getUniqueEntity(): ?object
    {
        $result = $this->getEntities();

        $first = null;
        foreach ($result as $object) {
            if (null !== $first) {
                throw FluentQueryException::createNonUnique();
            }
            if (null === $object) {
                throw FluentQueryException::null();
            }
            $first = $object;
        }

        return $first;
    }

    public function getSortDefinition(): SortDefinition
    {
        return $this->sortDefinition;
    }

    public function getConditionDefinition(): ConditionDefinition
    {
        return $this->conditionDefinition;
    }

    public function getSliceDefinition(): SliceDefinition
    {
        return $this->sliceDefinition;
    }
}
