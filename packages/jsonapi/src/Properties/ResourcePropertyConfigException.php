<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use Exception;

class ResourcePropertyConfigException extends Exception
{
    private const PHRASE_ATTRIBUTE = 'an attribute';
    private const PHRASE_TO_ONE = 'a to-one relationship';
    private const PHRASE_TO_MANY = 'a to-many relationship';
    
    /**
     * @param non-empty-string $propertyName
     */
    public static function attributeAlreadyToOneRelationship(string $propertyName): self
    {
        return new self(self::buildMessage($propertyName, self::PHRASE_TO_ONE, self::PHRASE_ATTRIBUTE));
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function attributeAlreadyToManyRelationship(string $propertyName): self
    {
        return new self(self::buildMessage($propertyName, self::PHRASE_TO_MANY, self::PHRASE_ATTRIBUTE));
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function toOneRelationshipAlreadyAttribute(string $propertyName): self
    {
        return new self(self::buildMessage($propertyName, self::PHRASE_ATTRIBUTE, self::PHRASE_TO_ONE));
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function toOneRelationshipAlreadyToManyRelationship(string $propertyName): self
    {
        return new self(self::buildMessage($propertyName, self::PHRASE_TO_MANY, self::PHRASE_TO_ONE));
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function toManyRelationshipAlreadyAttribute(string $propertyName): self
    {
        return new self(self::buildMessage($propertyName, self::PHRASE_ATTRIBUTE, self::PHRASE_TO_MANY));
    }

    /**
     * @param non-empty-string $propertyName
     */
    public static function toManyRelationshipAlreadyToOneRelationship(string $propertyName): self
    {
        return new self(self::buildMessage($propertyName, self::PHRASE_TO_ONE, self::PHRASE_TO_MANY));
    }

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
     * @param non-empty-string $actual
     * @param non-empty-string $attempt
     *
     * @return non-empty-string
     */
    protected static function buildMessage(string $propertyName, string $actual, string $attempt): string
    {
        return "Property name '$propertyName' was already used to configure $actual and can not be used to configure $attempt.";
    }
}
