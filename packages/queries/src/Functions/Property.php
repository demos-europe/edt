<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Utilities\Iterables;

/**
 * @template-implements FunctionInterface<mixed>
 */
class Property implements FunctionInterface
{
    /**
     * @var PropertyPathAccessInterface
     */
    private $propertyPath;

    public function __construct(PropertyPathAccessInterface $propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    public function apply(array $propertyValues)
    {
        return Iterables::getOnlyValue($propertyValues);
    }

    public function getPropertyPaths(): iterable
    {
        return [$this->propertyPath];
    }

    public function __toString(): string
    {
        $class = static::class;
        return "{$class}($this->propertyPath)";
    }
}
