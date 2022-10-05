<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * @template TEntity of object
 */
interface EntityBasedInterface
{
    /**
     * @return class-string<TEntity> The fully qualified name of the class backing entities this type defines.
     */
    public function getEntityClass(): string;
}
