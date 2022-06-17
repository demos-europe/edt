<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Utilities\Iterables;

/**
 * This trait is to be used when an implementation of {@link FunctionInterface} invokes
 * multiple other {@link FunctionInterface}s.
 *
 * **Make sure to invoke the {@link FunctionBasedTrait::setFunctions()} method before using
 * any methods provided by the trait.**
 *
 * This trait provides a {@link FunctionInterface::apply} implementation that takes a flat list of
 * parameters intended for the "parent" function and splits this list up so that
 * the right ones are passed into the "child" functions.
 *
 * The counts are expected to match, meaning if two child function exist and the first one
 * expects 2 parameters and the second one 3 parameters, then 5 parameters must be passed
 * into the the parent function. Otherwise the behavior is undefined.
 *
 * @template T
 */
trait MultiFunctionTrait
{
    use FunctionBasedTrait;
    /**
     * @var callable(...mixed): T
     */
    private $callback;

    /**
     * @return T
     */
    public function apply(array $propertyValues)
    {
        $functionCount = Iterables::count($this->functions);
        $nestedPropertyValues = $this->unflatPropertyValues($propertyValues);
        Iterables::assertCount($functionCount, $nestedPropertyValues);
        $functionResults = array_map(static function (FunctionInterface $function, array $functionParams) {
            return $function->apply($functionParams);
        }, $this->functions, $nestedPropertyValues);
        return ($this->callback)(...$functionResults);
    }
}
