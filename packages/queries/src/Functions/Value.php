<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use Webmozart\Assert\Assert;

/**
 * @template TOutput
 * @template-implements FunctionInterface<TOutput>
 */
class Value implements FunctionInterface
{
    /**
     * @param TOutput $value
     */
    public function __construct(
        protected  $value
    ) {}

    public function apply(array $propertyValues)
    {
        Assert::count($propertyValues, 0);
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
