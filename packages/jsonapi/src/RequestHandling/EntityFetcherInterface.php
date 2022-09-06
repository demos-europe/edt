<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\ReadableTypeInterface;
use InvalidArgumentException;

interface EntityFetcherInterface
{
    /**
     * @template O of object
     *
     * @param IdentifiableTypeInterface<O>&ReadableTypeInterface<O> $type
     *
     * @return O
     *
     * @throws AccessException          thrown if the resource type denies the currently logged-in user
     *                                  the access to the resource type needed to fulfill the request
     * @throws InvalidArgumentException thrown if no entity with the given ID and resource type was found
     */
    public function getEntityByTypeIdentifier(IdentifiableTypeInterface $type, string $id): object;
}
