<?php

declare(strict_types=1);

namespace EDT\JsonApi\Utilities;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use InvalidArgumentException;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
interface ResourceTypeByClassProviderInterface
{
    /**
     * @template TEntity of object
     *
     * @param class-string $resourceTypeClass
     * @param class-string<TEntity> $expectedEntityClass
     *
     * @return ResourceTypeInterface<TCondition, TSorting, TEntity>
     *
     * @throws InvalidArgumentException
     */
    public function getResourceTypeByClass(string $resourceTypeClass, string $expectedEntityClass): ResourceTypeInterface;
}
