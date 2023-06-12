<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use Exception;

class OpenApiGenerateException extends Exception
{
    /**
     * @param non-empty-string $propertyName
     * @param non-empty-string $typeName
     * @param Exception $exception
     */
    public static function attributeType(string $propertyName, string $typeName, Exception $exception): self
    {
        return new self("Could not determine attribute type of resource property $typeName::$propertyName", 0, $exception);
    }
}
