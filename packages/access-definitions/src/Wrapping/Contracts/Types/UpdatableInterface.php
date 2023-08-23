<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts\Types;

use EDT\JsonApi\RequestHandling\ExpectedPropertyCollection;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\EntityDataInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 */
interface UpdatableInterface
{
    public function getExpectedUpdateProperties(): ExpectedPropertyCollection;

    /**
     * @param non-empty-string $entityId
     *
     * @return TEntity|null $entity `null` if the entity was updated exactly as defined in the request
     */
    public function updateEntity(string $entityId, EntityDataInterface $entityData): ?object;
}
