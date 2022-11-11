<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities\TypeAccessors;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\PropertyAccessException;
use EDT\Wrapping\Contracts\RelationshipAccessException;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use Exception;

/**
 * @template-extends AbstractProcessorConfig<SortableTypeInterface<PathsBasedInterface, PathsBasedInterface, object>>
 */
class ExternSortableProcessorConfig extends AbstractProcessorConfig
{
    public function getPropertyType(TypeInterface $type, string $property): ?TypeInterface
    {
        $availableProperties = $type->getSortableProperties();
        // abort if the (originally accessed/non-de-aliased) property is not available
        if (!array_key_exists($property, $availableProperties)) {
            $availablePropertyNames = array_keys($availableProperties);
            throw PropertyAccessException::propertyNotAvailableInType($property, $type, ...$availablePropertyNames);
        }

        $targetType = $availableProperties[$property];
        if (null === $targetType) {
            return null;
        }

        if (!$targetType instanceof ExposableRelationshipTypeInterface
            || !$targetType->isExposedAsRelationship()) {
            throw RelationshipAccessException::notExposedRelationship($targetType);
        }

        if (!$targetType instanceof SortableTypeInterface){
            throw RelationshipAccessException::typeNotSortable($type);
        }

        return $targetType;
    }

    public function getProperties(TypeInterface $type): array
    {
        throw new Exception('Not implemented.');
    }

    public function getRelationshipType(string $typeIdentifier): TypeInterface
    {
        throw new Exception('Not implemented.');
    }
}
