<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use function array_key_exists;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use function is_object;

class CachingPropertyReader extends PropertyReader
{
    /**
     * @var array<non-empty-string, object|null>
     */
    private array $toOneValueCache = [];

    /**
     * @var array<non-empty-string, list<object>>
     */
    private array $toManyValueCache = [];

    public function determineToOneRelationshipValue(TransferableTypeInterface $relationshipType, object $relationshipEntity): ?object
    {
        $hash = $this->createHash($relationshipType, $relationshipEntity);
        if (!array_key_exists($hash, $this->toOneValueCache)) {
            $relationshipEntity = parent::determineToOneRelationshipValue($relationshipType, $relationshipEntity);
            $this->toOneValueCache[$hash] = $relationshipEntity;

            return $relationshipEntity;
        }

        return $this->toOneValueCache[$hash];
    }

    public function determineToManyRelationshipValue(TransferableTypeInterface $relationshipType, array $relationshipEntities): array
    {
        $hash = $this->createHash($relationshipType, $relationshipEntities);
        if (!array_key_exists($hash, $this->toManyValueCache)) {
            $relationshipEntities =  parent::determineToManyRelationshipValue($relationshipType, $relationshipEntities);
            $this->toManyValueCache[$hash] = $relationshipEntities;

            return $relationshipEntities;
        }

        return $this->toManyValueCache[$hash];
    }

    /**
     * Create cache hash from 2 values that need to be distinct to be cached.
     *
     * @template TEntity of object
     *
     * @param TransferableTypeInterface<FunctionInterface<bool>, SortMethodInterface, TEntity> $relationshipType
     * @param TEntity|list<TEntity> $relationshipValue
     *
     * @return non-empty-string
     */
    private function createHash(TransferableTypeInterface $relationshipType, object|array $relationshipValue): string
    {
        $hashRelationship = spl_object_hash($relationshipType);

        if (is_object($relationshipValue)) {
            $hashProperty = spl_object_hash($relationshipValue);
        } else {
            $hashProperty = serialize($relationshipValue);
        }

        return hash('sha256', $hashRelationship.$hashProperty);
    }
}
