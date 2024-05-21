<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use Exception;

/**
 * @template TEntity of object
 */
interface GetableTypeInterface extends ReadableTypeInterface, NamedTypeInterface
{
    /**
     * Get an instance of the entity corresponding to this type with the given identifier that matches the
     * given conditions.
     *
     * Implementations are responsible to not return instances with restricted accessibility.
     *
     * @param non-empty-string $identifier
     *
     * @return TEntity
     *
     * @throws Exception
     */
    public function getEntity(string $identifier): object;
}
