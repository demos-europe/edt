<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use InvalidArgumentException;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
interface EntityFetcherInterface
{
    /**
     * @template O of object
     *
     * @param TypeInterface<TCondition, TSorting, O>&IdentifiableTypeInterface<TCondition, TSorting, O>&TransferableTypeInterface<TCondition, TSorting, O> $type
     * @param non-empty-string                                                                           $id
     *
     * @return O
     *
     * @throws AccessException          thrown if the resource type denies the currently logged-in user
     *                                  the access to the resource type needed to fulfill the request
     * @throws InvalidArgumentException thrown if no entity with the given ID and resource type was found
     */
    public function getEntityByTypeIdentifier(IdentifiableTypeInterface $type, string $id): object;
}
