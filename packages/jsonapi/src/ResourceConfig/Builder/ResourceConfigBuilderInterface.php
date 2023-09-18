<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\JsonApi\ResourceConfig\ResourceConfigInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
interface ResourceConfigBuilderInterface
{
    /**
     * @return ResourceConfigInterface<TCondition, TSorting, TEntity>
     */
    public function build(): ResourceConfigInterface;
}
