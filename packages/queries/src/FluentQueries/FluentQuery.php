<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\ObjectProviderInterface;
use EDT\Querying\Contracts\FluentQueryException;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\SliceException;
use EDT\Querying\Contracts\SortException;
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
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template T of object
 */
class FluentQuery
{
    /**
     * @var ObjectProviderInterface<C, T>
     */
    protected $objectProvider;
    /**
     * @var ConditionDefinition<C>
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
     * @param ObjectProviderInterface<C, T> $objectProvider
     * @param ConditionDefinition<C>     $conditionDefinition
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
     * @param ConditionFactoryInterface<C> $conditionFactory
     * @param ObjectProviderInterface<C, T> $objectProvider
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
     *
     * @throws PathException
     * @throws SliceException
     * @throws SortException
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
     * @throws PathException
     * @throws SliceException
     * @throws SortException
     */
    public function getUniqueEntity(): ?object
    {
        $result = $this->getEntities();

        $first = null;
        foreach ($result as $object) {
            if (null !== $first) {
                throw FluentQueryException::createNonUnique();
            }
            // minimal safeguard against invalid object provider implementation
            // @phpstan-ignore-next-line
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
