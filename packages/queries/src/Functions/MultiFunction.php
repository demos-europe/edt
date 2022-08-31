<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Utilities\Iterables;
use function count;

/**
 * This class is to be used when an implementation of {@link FunctionInterface} invokes
 * multiple other {@link FunctionInterface}s.
 *
 * This class provides a {@link FunctionInterface::apply} implementation that takes a flat list of
 * parameters intended for the "parent" function and splits this list up so that
 * the right ones are passed into the "child" functions.
 *
 * The counts are expected to match, meaning if two child function exist and the first one
 * expects 2 parameters and the second one 3 parameters, then 5 parameters must be passed
 * into the parent function. Otherwise, the behavior is undefined.
 *
 * @template T
 */
class MultiFunction implements FunctionInterface
{
    use FunctionBasedTrait;

    /**
     * @var callable(mixed...): T
     */
    private $callback;

    /**
     * @param callable(mixed...): T
     * @param FunctionInterface<mixed> $function
     * @param FunctionInterface<mixed> ...$functions
     */
    public function __construct(callable $callback, FunctionInterface $function, FunctionInterface... $functions)
    {
        $this->setFunctions($function, ...$functions);
        $this->callback = $callback;
    }

    /**
     * @return T
     */
    public function apply(array $propertyValues)
    {
        $functionCount = count($this->functions);
        $nestedPropertyValues = $this->unflatPropertyValues($propertyValues);
        Iterables::assertCount($functionCount, $nestedPropertyValues);
        $functionResults = array_map(static function (FunctionInterface $function, array $functionParams) {
            return $function->apply($functionParams);
        }, $this->functions, $nestedPropertyValues);
        return ($this->callback)(...$functionResults);
    }
}
