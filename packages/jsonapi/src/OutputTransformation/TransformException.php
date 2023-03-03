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
}
