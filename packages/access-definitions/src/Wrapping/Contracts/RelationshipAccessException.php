<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts;

use EDT\Wrapping\Contracts\Types\TypeInterface;
use function get_class;

class RelationshipAccessException extends PropertyAccessException
{
    /**
     * @var non-empty-string|null
     */
    protected ?string $relationshipTypeIdentifier = null;

    /**
     * @var class-string<TypeInterface>|null
     */
    protected ?string $relationshipTypeClass = null;

    /**
     * @param non-empty-string $property
     */
    public static function relationshipTypeAccess(TypeInterface $type, string $property, TypeRetrievalAccessException $previous): self
    {
        $typeClass = get_class($type);
        $relationshipTypeIdentifier = $previous->getTypeClass();
        $self = new self($property, "Property '$property' is available and a relationship in the type class '$typeClass', but its destination type '$relationshipTypeIdentifier' is not accessible.", 0, $previous);
        $self->typeClass = $typeClass;
        $self->relationshipTypeIdentifier = $relationshipTypeIdentifier;
        $self->relationshipTypeClass = $previous->getTypeClass();

        return $self;
    }

    /**
     * @param non-empty-string $propertyName
     * @param int|string       $key
     */
    public static function toManyWithRestrictedItemNotSetable(TypeInterface $type, string $propertyName, string $deAliasedPropertyName, $key): self
    {
        $typeClass = get_class($type);
        $self = new self($propertyName, "Can't set a list into the to-many relationship '$propertyName' (de-aliased to '$deAliasedPropertyName') in type class '$typeClass' if said list contains a non-accessible (due to their type class '$typeClass') items stored under the key '$key'.");
        $self->typeClass = $typeClass;

        return $self;
    }

    /**
     * @param non-empty-string $propertyName
     * @param non-empty-string $deAliasedPropertyName
     */
    public static function toOneWithRestrictedItemNotSetable(TypeInterface $type, string $propertyName, string $deAliasedPropertyName): self
    {
        $typeClass = get_class($type);
        $self = new self($propertyName, "Can't set an object into the to-one relationship '$propertyName' (de-aliased to '$deAliasedPropertyName') in type class '$typeClass' if said object is non-accessible due to its type class '$typeClass'.");
        $self->typeClass = $typeClass;

        return $self;
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function toManyNotIterable(string $propertyName): self
    {
        return new self($propertyName, "Attempted to use non-iterable data for a to-many relationship property '$propertyName'.");
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function toOneNeitherObjectNorNull(string $propertyName): self
    {
        return new self($propertyName, "Attempted to use a value that was neither `null` nor the expected entity type for a relationship property '$propertyName'.");
    }

    /**
     * @return non-empty-string|null
     */
    public function getRelationshipTypeIdentifier(): ?string
    {
        return $this->relationshipTypeIdentifier;
    }

    /**
     * @return class-string<TypeInterface>|null
     */
    public function getRelationshipTypeClass(): ?string
    {
        return $this->relationshipTypeClass;
    }
}
