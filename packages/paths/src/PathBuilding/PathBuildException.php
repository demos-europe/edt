<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use Exception;
use EDT\Querying\Contracts\PathException;

class PathBuildException extends PathException
{
    public static function getPropertyFailed(string $class, string $propertyName, Exception $previous): self
    {
        return new self("Parse failed for class '$class' when requesting property '$propertyName'.", 0, $previous);
    }

    public static function childWithEmptyParentPropertyName(): self
    {
        return new self('Property name of parent must not be empty.');
    }

    public static function genericCreateChild(string $class, string $propertyName, Exception $previous): self
    {
        return new self("Could not create child with parent class '$class' with property '$propertyName'.", 0, $previous);
    }

    public static function startPathFailed(string $class, Exception $previous): self
    {
        return new self("Could not create path starting point with parent class '$class'.", 0, $previous);
    }

    /**
     * @param non-empty-string $propertyName
     * @param class-string $className
     * @param non-empty-list<PropertyTag> $propertyTags
     */
    public static function createFromName(string $propertyName, string $className, array $propertyTags): self
    {
        $tagIdentifiers = implode(
            ', ',
            array_map(
                static fn (PropertyTag $propertyTag): string => $propertyTag->value,
                $propertyTags
            )
        );

        return new self("The property '$propertyName' is not available in the class '$className'. Looked for the following docblock tags: $tagIdentifiers");
    }

    public static function missingInterface(string $class, string $interface): self
    {
        return new self("The property class '$class' must implement '$interface' to start a path.");
    }
}
