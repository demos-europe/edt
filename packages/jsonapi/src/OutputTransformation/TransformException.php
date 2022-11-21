<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use Exception;
use League\Fractal\TransformerAbstract;

/**
 * Use this exception or extending classes to imply a problem when transforming resources via
 * classes extending from {@link TransformerAbstract}.
 */
class TransformException extends Exception
{
    public static function nullScope(): self
    {
        return new self('Scope was unexpectedly null.');
    }

    /**
     * @param non-empty-string $includeName
     */
    public static function includeNotAvailable(string $includeName): self
    {
        return new self("Include '$includeName' is not available");
    }

    public static function noIncludeMethod(string $includeMethodName): self
    {
        return new self("No method found for: $includeMethodName");
    }

    public static function substring(Exception $exception): self
    {
        return new self('Failed to create include name', 0, $exception);
    }

    public static function nonAttributeValue(string $valueType): self
    {
        return new self("The type '$valueType' is not valid as type of attribute.");
    }

    /**
     * @param non-empty-string $targetType
     */
    public static function nonToOneType(string $relationshipType, string $targetType): self
    {
        return new self("The type '$relationshipType' is not valid as type of a to-one relationship with the target '$targetType'.");
    }

    public static function nonToManyIterable(string $relationshipType): self
    {
        return new self("The type '$relationshipType' is not iterable und thus not valid as type of a to-many relationship.");
    }

    /**
     * @param non-empty-string $targetType
     */
    public static function nonToManyNestedType(string $relationshipType, string $targetType): self
    {
        return new self("The type '$relationshipType' is not valid as target type of a to-many relationship with the target '$targetType'.");
    }
}
