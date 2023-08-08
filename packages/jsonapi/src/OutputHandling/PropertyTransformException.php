<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputHandling;

use Exception;

/**
 * Thrown if the transformation of a specific property failed.
 */
class PropertyTransformException extends Exception
{
    /**
     * @param non-empty-string $propertyName
     */
    public function __construct(protected readonly string $propertyName, Exception $previous)
    {
        parent::__construct("Failed to transform property '$this->propertyName'.", 0, $previous);
    }

    /**
     * @return non-empty-string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
}
