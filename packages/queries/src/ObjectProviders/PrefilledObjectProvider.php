<?php

declare(strict_types=1);

namespace EDT\Querying\ObjectProviders;

use EDT\Querying\Pagination\OffsetBasedPagination;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\SliceException;
use EDT\Querying\Contracts\SortException;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\EntityProviders\OffsetBasedEntityProviderInterface;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Contracts\ObjectProviderInterface;
use function array_slice;

/**
 * Implements {@link ObjectProviderInterface::getObjects} by applying the parameters to an array of
 * entities that was given on instantiation and returning the result.
 *
 * @template T of object
 * @template K of int|string
 * @template-implements ObjectProviderInterface<FunctionInterface<bool>, SortMethodInterface, T>
 * @template-implements OffsetBasedEntityProviderInterface<FunctionInterface<bool>, SortMethodInterface, T>
 *
 * TODO: rename to PrefilledEntityProvider
 */
class PrefilledObjectProvider implements ObjectProviderInterface, OffsetBasedEntityProviderInterface
{
    /**
     * @var array<K, T>
     */
    private $prefilledArray;

    /**
     * @var ConditionEvaluator
     */
    private $conditionEvaluator;

    /**
     * @var Sorter
     */
    private $sorter;

    /**
     * @param array<K, T>             $prefilledArray
     * @param ConditionEvaluator|null $conditionEvaluator
     */
    // TODO: refactor default away and inject Sorter
    public function __construct(PropertyAccessorInterface $propertyAccessor, array $prefilledArray, ConditionEvaluator $conditionEvaluator = null)
    {
        $this->prefilledArray = $prefilledArray;
        $this->conditionEvaluator = $conditionEvaluator ?? new ConditionEvaluator($propertyAccessor);
        $this->sorter = new Sorter($propertyAccessor);
    }

    /**
     * @return array<K, T>
     *
     * @inheritDoc
     */
    public function getObjects(array $conditions, array $sortMethods = [], int $offset = 0, int $limit = null): iterable
    {
        $result = $this->prefilledArray;
        $result = $this->filter($result, $conditions);
        $result = $this->sort($result, $sortMethods);
        $result = $this->slice($result, $offset, $limit);

        return $result;
    }

    /**
     * @param list<FunctionInterface<bool>> $conditions
     * @param list<SortMethodInterface>     $sortMethods
     * @param OffsetBasedPagination|null    $pagination
     *
     * @return array<K, T>
     *
     * @throws SliceException
     * @throws SortException
     */
    public function getEntities(array $conditions, array $sortMethods, ?object $pagination): array
    {
        $result = $this->prefilledArray;
        $result = $this->filter($result, $conditions);
        $result = $this->sort($result, $sortMethods);
        $result = null === $pagination
            ? $this->slice($result, 0, null)
            : $this->slice($result, $pagination->getOffset(), $pagination->getLimit());

        return $result;
    }

    /**
     * @param array<K, T> $list
     * @param list<SortMethodInterface> $sortMethods
     * @return array<K, T>
     *
     * @throws SortException
     */
    protected function sort(array $list, array $sortMethods): array
    {
        if ([] === $sortMethods) {
            return $list;
        }

        return $this->sorter->sortArray($list, $sortMethods);
    }

    /**
     * @param array<K, T>                   $list
     * @param list<FunctionInterface<bool>> $conditions
     *
     * @return array<K,T>
     */
    protected function filter(array $list, array $conditions): array
    {
        if ([] !== $conditions) {
            $list = $this->conditionEvaluator->filterArray($list, ...$conditions);
        }

        return $list;
    }

    /**
     * @param array<K, T> $list
     * @return array<K, T>
     * @throws SliceException
     */
    protected function slice(array $list, int $offset, ?int $limit): array
    {
        if (0 > $offset) {
            throw SliceException::negativeOffset($offset);
        }
        if (0 > $limit) {
            throw SliceException::negativeLimit($limit);
        }
        if (0 !== $offset || null !== $limit) {
            $list = array_slice($list, $offset, $limit);
        }

        return $list;
    }
}
