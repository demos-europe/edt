<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Utilities\Iterables;
use function count;

/**
 * This trait is to be used for any {@link FunctionInterface} implementation that itself
 * calls one or multiple other functions.
 *
 * **Make sure to invoke the {@link FunctionBasedTrait::setFunctions()} method before using
 * any methods provided by the trait.**
 */
trait FunctionBasedTrait
{
    /**
     * @var array<int,FunctionInterface<mixed>>
     */
    private $functions = [];

    /**
     * Returns all {@link PropertyPathAccessInterface} instances of all
     * {@link FunctionBasedTrait::$functions} as a flat iterable.
     *
     * @return iterable<PropertyPathAccessInterface>
     */
    public function getPropertyPaths(): iterable
    {
        return Iterables::flat(static function (PathsBasedInterface $function): array {
            return Iterables::asArray($function->getPropertyPaths());
        }, $this->functions);
    }

    public function __toString(): string
    {
        $class = static::class;
        $functionList = implode(
            ',',
            array_map(
                static function (FunctionInterface $function): string {
                    return (string)$function;
                },
                $this->functions
            )
        );
        return "$class($functionList)";
    }

    /**
     * @param FunctionInterface<mixed> $function
     * @param FunctionInterface<mixed> ...$functions
     */
    private function setFunctions(FunctionInterface $function, FunctionInterface ...$functions): void
    {
        array_push($this->functions, $function, ...$functions);
    }

    /**
     * In case the using implementation has set only one instance into {@link FunctionBasedTrait::$functions}
     * you can use this method to easily access it. An exception will be thrown if not
     * exactly one {@link FunctionInterface} instance is set in {@link FunctionBasedTrait::$functions}
     * when this method is called.
     *
     * @return FunctionInterface<mixed>
     */
    private function getOnlyFunction(): FunctionInterface
    {
        Iterables::assertCount(1, $this->functions);
        return $this->functions[0];
    }

    /**
     * When the implementation using this trait is evaluated a flat list of parameters will
     * be provided. This method will split this flat list into multiple arrays, each one
     * intended to be passed into the instance in {@link FunctionBasedTrait::$functions} with
     * the same index as the nested array.
     *
     * The assumption of this method is that all parameters for the child functions and
     * **only** those are present in the given array. If the implementation using this trait
     * expects some parameters for itself they need to be removed from the $propertyValues
     * before calling this method.
     *
     * @template T
     *
     * @param T[] $propertyValues
     *
     * @return T[][]
     */
    private function unflatPropertyValues(array $propertyValues): array
    {
        $propertyAliasCountables = array_map(static function (PathsBasedInterface $pathsBased): int {
            return Iterables::count($pathsBased->getPropertyPaths());
        }, $this->functions);
        return Iterables::split($propertyValues, false, ...$propertyAliasCountables);
    }

    /**
     * Call the {@link FunctionInterface::apply} method of all {@link FunctionBasedTrait::$functions}.
     *
     * This method expects all instances in {@link FunctionBasedTrait::$functions} to return a `bool`
     * value. All returns are conjunct based on the $stopEvaluation parameter and the resulting `bool`
     * value returned.
     *
     * @param callable(bool): bool $stopEvaluation Determines how the evaluation results of all conditions are
     *                                 conjunct. If the callback reverts its bool parameter it acts
     *                                 as `AND`. If it is simply returns the bool parameter it acts
     *                                 as `OR`.
     * @param mixed[]  $parameters Will be split up to provide a separate parameter array for each instance in
     *                             {@link FunctionBasedTrait::$functions}, see {@link FunctionBasedTrait::unflatPropertyValues()}
     *                             for details.
     */
    private function evaluateAll(callable $stopEvaluation, array $parameters): bool
    {
        $nestedPropertyValues = $this->unflatPropertyValues($parameters);
        Iterables::assertCount(count($nestedPropertyValues), $this->functions);
        $conditionCalls = array_map(static function (FunctionInterface $condition, array $propertyValues): callable {
            return static function () use ($condition, $propertyValues): bool {
                return $condition->apply($propertyValues);
            };
        }, $this->functions, $nestedPropertyValues);
        return Iterables::earlyBreakEvaluate($stopEvaluation, ...$conditionCalls);
    }
}
