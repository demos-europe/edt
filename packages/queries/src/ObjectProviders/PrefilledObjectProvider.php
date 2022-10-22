<?php

declare(strict_types=1);

namespace EDT\Querying\ObjectProviders;

use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PaginationException;
use EDT\Querying\Contracts\SortException;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\EntityProviders\OffsetPaginatingEntityProviderInterface;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Contracts\ObjectProviderInterface;
use function array_slice;

/**
 * Implements {@link ObjectProviderInterface::getObjects} by applying the parameters to an array of
 * entities that was given on instantiation and returning the result.
 *
 * @template TEntity of object
 * @template TKey of int|string
 * @template-implements ObjectProviderInterface<FunctionInterface<bool>, SortMethodInterface, TEntity>
 * @template-implements OffsetPaginatingEntityProviderInterface<FunctionInterface<bool>, SortMethodInterface, TEntity>
 *
 * TODO: rename to PrefilledEntityProvider
 */
class PrefilledObjectProvider implements ObjectProviderInterface, OffsetPaginatingEntityProviderInterface
{
    /**
     * @var array<TKey, TEntity>
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
     * @param array<TKey, TEntity> $prefilledArray
     */
    public function __construct(ConditionEvaluator $conditionEvaluator, Sorter $sorter, array $prefilledArray)
    {
        $this->prefilledArray = $prefilledArray;
        $this->conditionEvaluator = $conditionEvaluator;
        $this->sorter = $sorter;
    }

    /**
     * @return array<TKey, TEntity>
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
     * @param OffsetPagination|null         $pagination
     *
     * @return array<TKey, TEntity>
     *
     * @throws PaginationException
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
     * @param array<TKey, TEntity> $list
     * @param list<SortMethodInterface> $sortMethods
     * @return array<TKey, TEntity>
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
     * @param array<TKey, TEntity>                   $list
     * @param list<FunctionInterface<bool>> $conditions
     *
     * @return array<TKey,TEntity>
     */
    protected function filter(array $list, array $conditions): array
    {
        if ([] !== $conditions) {
            $list = $this->conditionEvaluator->filterArray($list, ...$conditions);
        }

        return $list;
    }

    /**
     * @param array<TKey, TEntity> $list
     * @param int<0, max>          $offset
     * @param int<0, max>|null     $limit
     *
     * @return array<TKey, TEntity>
     * @throws PaginationException
     */
    protected function slice(array $list, int $offset, ?int $limit): array
    {
        if (0 !== $offset || null !== $limit) {
            $list = array_slice($list, $offset, $limit);
        }

        return $list;
    }
}
