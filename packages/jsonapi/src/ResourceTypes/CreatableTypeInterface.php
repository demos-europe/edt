<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\RequestHandling\ExpectedPropertyCollectionInterface;
use EDT\JsonApi\RequestHandling\ModifiedEntity;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use EDT\Wrapping\CreationDataInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
interface CreatableTypeInterface extends NamedTypeInterface, ReadableTypeInterface
{
    /**
     * The return defines what properties are needed and allowed to be set when a new instance of
     * the {@link EntityBasedInterface::getEntityClass() backing class} is to be created.
     */
    public function getExpectedInitializationProperties(): ExpectedPropertyCollectionInterface;

    public function createEntity(CreationDataInterface $entityData): ModifiedEntity;
}
