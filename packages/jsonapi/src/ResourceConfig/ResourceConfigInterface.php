<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use EDT\Wrapping\Contracts\Types\PropertyUpdatableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;
use EDT\Wrapping\ResourceBehavior\ResourceInstantiability;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends PropertyUpdatableTypeInterface<TCondition, TSorting, TEntity>
 * @template-extends PropertyReadableTypeInterface<TCondition, TSorting, TEntity>
 * @template-extends SortingTypeInterface<TCondition, TSorting>
 * @template-extends FilteringTypeInterface<TCondition, TSorting>
 */
interface ResourceConfigInterface extends PropertyUpdatableTypeInterface, FilteringTypeInterface, SortingTypeInterface, PropertyReadableTypeInterface
{
    /**
     * @return ResourceInstantiability<TEntity>
     */
    public function getInstantiability(): ResourceInstantiability;
}
