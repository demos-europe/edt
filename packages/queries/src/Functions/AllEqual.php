<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Utilities\Iterables;

/**
 * @template-implements FunctionInterface<bool>
 */
class AllEqual implements FunctionInterface
{
    use FunctionBasedTrait;

    /**
     * @param FunctionInterface<mixed> $firstFunction
     * @param FunctionInterface<mixed> $secondFunction
     * @param FunctionInterface<mixed> ...$additionalFunctions
     */
    public function __construct(FunctionInterface $firstFunction, FunctionInterface $secondFunction, FunctionInterface ...$additionalFunctions)
    {
        $this->setFunctions($firstFunction, $secondFunction, ...$additionalFunctions);
    }

    public function apply(array $propertyValues): bool
    {
        $nestedPropertyValues = $this->unflatPropertyValues($propertyValues);
        $evaluateCalls = array_map(static function (FunctionInterface $function, array $propertyValues): callable {
            return static function () use ($function, $propertyValues) {
                return $function->apply($propertyValues);
            };
        }, $this->functions, $nestedPropertyValues);
        return Iterables::earlyBreakAnd(static function ($previous, $current): bool {
            return null === $previous || $previous !== $current;
        }, ...$evaluateCalls);
    }
}
