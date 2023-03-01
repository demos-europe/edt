<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use Exception;

class ResourcePropertyConfigException extends Exception
{
    /**
     * @param non-empty-string $propertyName
     * @param non-empty-string $currentRelationshipTypeName
     * @param non-empty-string $attemptedRelationshipTypeName
     */
    public static function relationshipType(string $propertyName, string $currentRelationshipTypeName, string $attemptedRelationshipTypeName): self
    {
        return new self("Relationship property '$propertyName' can not be configured with the target relationship type '$attemptedRelationshipTypeName' as it was already configured with a the target relationship type '$currentRelationshipTypeName'.");
    }

    /**
     * @param class-string $implementation
     * @param non-empty-string $adjective
     */
    public static function missingImplementation(string $implementation, string $adjective): self
    {
        return new self("Attempted to set relationship property as $adjective whose target type does not implement '$implementation'.");
    }

    /**
     * @param non-empty-string $expectedStart
     */
    public static function invalidStart(string $expectedStart): self
    {
        return new self("The given path has a different starting point than expected. Expected '$expectedStart'.");
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function propertyAlreadyDefinedAsOneRelationship(string $propertyName): self
    {
        return new self("The to-one relationship '$propertyName' was already configured with a different type.");
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function propertyAlreadyDefinedAsToMany(string $propertyName): self
    {
        return new self("The property '$propertyName' was already configured as to-many relationship.");
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function propertyAlreadyDefinedAsAttribute(string $propertyName): self
    {
        return new self("The property '$propertyName' was already configured as JSON attribute.");
    }
}
