<?php

declare(strict_types=1);

namespace EDT\Wrapping\WrapperFactories;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Utilities\EntityVerifierInterface;

/**
 * Creates a wrapper around an instance of a {@link TypeInterface::getEntityClass() backing object}.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class WrapperObjectFactory
{
    /**
     * @param EntityVerifierInterface<TCondition, TSorting> $entityVerifier
     */
    public function __construct(
        protected readonly EntityVerifierInterface $entityVerifier
    ) {}

    /**
     * @template TEntity of object
     *
     * @param TEntity $entity
     * @param TransferableTypeInterface<TCondition, TSorting, TEntity> $type
     *
     * @return WrapperObject<TEntity, TCondition, TSorting>
     */
    public function createWrapper(object $entity, TransferableTypeInterface $type): WrapperObject
    {
        return new WrapperObject($entity, $type, $this, $this->entityVerifier);
    }
}
