<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\PropertyPaths\PathInfo;
use Webmozart\Assert\Assert;

/**
 * @template-implements FunctionInterface<mixed>
 */
class Property implements FunctionInterface
{
    public function __construct(
        private readonly PropertyPathAccessInterface $propertyPath
    ) {}

    public function apply(array $propertyValues)
    {
        Assert::count($propertyValues, 1);
        return array_pop($propertyValues);
    }

    public function getPropertyPaths(): array
    {
        return [new PathInfo($this->propertyPath, true)];
    }

    public function __toString(): string
    {
        $class = static::class;
        return "$class($this->propertyPath)";
    }
}
