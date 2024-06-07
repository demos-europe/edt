<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\PropertyPaths\PathInfo;
use EDT\Querying\Utilities\Iterables;
use function count;

/**
 * This class is to be used for any {@link FunctionInterface} implementation that itself
 * calls one or multiple other functions.
 *
 * @template TOutput
 * @template TInput
 * @template-implements FunctionInterface<TOutput>
 *
 * @internal
 */
abstract class AbstractFunction implements FunctionInterface
{
    private bool $toManyAllowed = true;

    /**
     * @var list<FunctionInterface<TInput>>
     */
    protected array $functions = [];

    /**
     * TODO (#151): change parameters to single non-empty-list
     *
     * @param FunctionInterface<TInput> $function
     * @param FunctionInterface<TInput> ...$functions
     */
    public function __construct(FunctionInterface $function, FunctionInterface ...$functions)
    {
        array_push($this->functions, $function, ...$functions);
    }

    /**
     * Returns all {@link PropertyPathAccessInterface} instances of all
     * {@link AbstractFunction::$functions} as a flat iterable.
     */
    public function getPropertyPaths(): array
    {
        return Iterables::mapFlat(
            fn (PathsBasedInterface $function): array => array_map(
                [$this, 'setToManyAllowed'],
                $function->getPropertyPaths()
            ),
            $this->functions
        );
    }

    public function __toString(): string
    {
        $class = static::class;
        $functionList = implode(
            ',',
            array_map(
                static fn (FunctionInterface $function): string => (string)$function,
                $this->functions
            )
        );
        return "$class($functionList)";
    }

    protected function setToManyAllowed(PathInfo $pathInfo): PathInfo
    {
        return PathInfo::maybeCopy($pathInfo, $this->toManyAllowed);
    }

    /**
     * When the implementation using this class is evaluated a flat list of parameters will
     * be provided. This method will split this flat list into multiple arrays, each one
     * intended to be passed into the instance in {@link AbstractFunction::$functions} with
     * the same index as the nested array.
     *
     * The assumption of this method is that all parameters for the child functions and
     * **only** those are present in the given array. If the implementation using this class
     * expects some parameters for itself they need to be removed from the $propertyValues
     * before calling this method.
     *
     * @template T
     *
     * @param list<T> $propertyValues
     *
     * @return list<list<T>>
     */
    protected function unflatPropertyValues(array $propertyValues): array
    {
        $propertyAliasCountables = array_map(
            static fn (PathsBasedInterface $pathsBased): int => count($pathsBased->getPropertyPaths()),
            $this->functions
        );

        return array_map('array_values', Iterables::split($propertyValues, $propertyAliasCountables));
    }
}
