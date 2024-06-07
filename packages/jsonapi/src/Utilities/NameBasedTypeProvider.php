<?php

declare(strict_types=1);

namespace EDT\JsonApi\Utilities;

use EDT\JsonApi\OutputHandling\PropertyReadableTypeProviderInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use InvalidArgumentException;
use Webmozart\Assert\Assert;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;

class NameBasedTypeProvider implements PropertyReadableTypeProviderInterface
{
    /**
     * @var array<non-empty-string, PropertyReadableTypeInterface<object>>
     */
    protected array $types = [];

    public function addType(string $typeName, PropertyReadableTypeInterface $type): void
    {
        $existingType = $this->types[$typeName] ?? null;
        // type already present, skip (we assume, that no new relationships appeared, since it was initially added to this instance)
        if ($type === $existingType) {
            return;
    	}

    	// type name used for a different type instance, abort
        if (null !== $existingType) {
            throw new InvalidArgumentException("A different type with the name `$typeName` was already added.");
    	}
    
    	// type not yet present, add it and attempt to add its relationships
        $this->types[$typeName] = $type;
    	foreach ($type->getReadability()->getRelationships() as $relationshipReadability) {
            $relationshipType = $relationshipReadability->getRelationshipType();
            if ($relationshipType instanceof PropertyReadableTypeInterface
                && $relationshipType instanceof NamedTypeInterface) {
                $this->addNamedType($relationshipType);
            }
        }
    }

    public function addNamedType(PropertyReadableTypeInterface&NamedTypeInterface $type): void
    {
        $this->addType($type->getTypeName(), $type);
    }

    public function getType(string $typeName): PropertyReadableTypeInterface
    {
        Assert::keyExists($this->types, $typeName);
        return $this->types[$typeName];
    }
}
