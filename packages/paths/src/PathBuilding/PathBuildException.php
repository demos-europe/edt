<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use Exception;
use EDT\Querying\Contracts\PathException;

class PathBuildException extends PathException
{
    public static function getPropertyFailed(string $class, string $propertyName, Exception $previous): self
    {
        return new self("Parse failed for class '$class' when requesting property '$propertyName'", 0, $previous);
    }

    public static function childWithEmptyParentPropertyName(): self
    {
        return new self('Property name of parent must not be empty.');
    }

    public static function genericCreateChild(string $class, string $propertyName, Exception $previous): self
    {
        return new self("Could not create child with parent class '$class' with property '$propertyName'.", 0, $previous);
    }

    public static function createFromName(string $propertyName, string $className, string $tagIdentifier, string ...$tagIdentifiers): self
    {
        array_unshift($tagIdentifiers, $tagIdentifier);
        $tagIdentifiers = implode(', ', $tagIdentifiers);

        return new self("The property '$propertyName' is not available in the class '$className'. Looked for the following docblock tags: $tagIdentifiers");
    }
}
