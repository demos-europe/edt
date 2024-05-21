<?php

declare(strict_types=1);

namespace EDT\Querying\ObjectProviders;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\OffsetEntityProviderInterface;
use EDT\Querying\Contracts\PaginationException;
use EDT\Querying\Contracts\SortException;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use function array_slice;

/**
 * @template TEntity of object
 *
 * @template-implements OffsetEntityProviderInterface<FunctionInterface<bool>, SortMethodInterface, TEntity>
 */
class PrefilledEntityProvider implements OffsetEntityProviderInterface
{
    /**
     * @param list<TEntity> $entities
     */
    public function __construct(
        protected readonly ConditionEvaluator $conditionEvaluator,
        protected readonly Sorter $sorter,
        protected array $entities = []
    ) {}

    public function getEntities(array $conditions, array $sortMethods, ?OffsetPagination $pagination): array
    {
        $result = $this->entities;
        $result = $this->filter($result, $conditions);
        $result = $this->sort($result, $sortMethods);

        return null === $pagination
            ? $this->slice($result, 0, null)
            : $this->slice($result, $pagination->getOffset(), $pagination->getLimit());
    }

    /**
     * @param list<TEntity> $list
     * @param list<SortMethodInterface> $sortMethods
     * @return list<TEntity>
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
     * @param list<TEntity> $list
     * @param list<FunctionInterface<bool>> $conditions
     *
     * @return list<TEntity>
     */
    protected function filter(array $list, array $conditions): array
    {
        if ([] !== $conditions) {
            return $this->conditionEvaluator->filterArray($list, ...$conditions);
        }

        return $list;
    }

    /**
     * @param list<TEntity> $list
     * @param int<0, max> $offset
     * @param int<0, max>|null $limit
     *
     * @return list<TEntity>
     * @throws PaginationException
     */
    protected function slice(array $list, int $offset, ?int $limit): array
    {
        if (0 !== $offset || null !== $limit) {
            return array_slice($list, $offset, $limit);
        }

        return $list;
    }
}
