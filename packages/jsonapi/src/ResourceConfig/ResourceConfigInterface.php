<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig;

use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use EDT\Wrapping\Contracts\Types\PropertyUpdatableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;
use EDT\Wrapping\ResourceBehavior\ResourceInstantiability;

/**
 * @template TEntity of object
 *
 * @template-extends PropertyUpdatableTypeInterface<TEntity>
 * @template-extends PropertyReadableTypeInterface<TEntity>
 */
interface ResourceConfigInterface extends PropertyUpdatableTypeInterface, FilteringTypeInterface, SortingTypeInterface, PropertyReadableTypeInterface
{
    /**
     * @return ResourceInstantiability<TEntity>
     */
    public function getInstantiability(): ResourceInstantiability;
}
