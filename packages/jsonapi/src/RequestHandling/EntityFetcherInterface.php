<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\ReadableTypeInterface;
use InvalidArgumentException;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface EntityFetcherInterface
{
    /**
     * @template O of object
     *
     * @param IdentifiableTypeInterface<C, S, O>&ReadableTypeInterface<C, S, O> $type
     * @param non-empty-string $id
     *
     * @return O
     *
     * @throws AccessException          thrown if the resource type denies the currently logged-in user
     *                                  the access to the resource type needed to fulfill the request
     * @throws InvalidArgumentException thrown if no entity with the given ID and resource type was found
     */
    public function getEntityByTypeIdentifier(IdentifiableTypeInterface $type, string $id): object;
}
