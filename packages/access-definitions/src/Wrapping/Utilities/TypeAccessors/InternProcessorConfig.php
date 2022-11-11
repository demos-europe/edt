<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities\TypeAccessors;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\PropertyAccessException;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use Exception;
use function array_key_exists;

/**
 * @template-extends AbstractProcessorConfig<TypeInterface<PathsBasedInterface, PathsBasedInterface, object>>
 */
class InternProcessorConfig extends AbstractProcessorConfig
{
    public function getPropertyType(TypeInterface $type, string $property): ?TypeInterface
    {
        $availableProperties = $type->getInternalProperties();
        // abort if the (originally accessed/non-de-aliased) property is not available
        if (!array_key_exists($property, $availableProperties)) {
            $availablePropertyNames = array_keys($availableProperties);
            throw PropertyAccessException::propertyNotAvailableInType($property, $type, ...$availablePropertyNames);
        }

        return $availableProperties[$property];
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
