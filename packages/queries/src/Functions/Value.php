<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Utilities\Iterables;

/**
 * @template R
 * @template-implements FunctionInterface<R>
 */
class Value implements FunctionInterface
{
    /**
     * @var R
     */
    protected $value;

    /**
     * @param R $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function apply(array $propertyValues)
    {
        Iterables::assertCount(0, $propertyValues);
        return $this->value;
    }

    public function getPropertyPaths(): array
    {
        return [];
    }

    public function __toString(): string
    {
        $class = static::class;
        return "$class($this->value)";
    }
}
