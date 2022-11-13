<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyAccessors;

use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Utilities\Iterables;
use ReflectionException;
use ReflectionProperty;
use function get_class;
use function array_slice;
use function is_array;

/**
 * Accesses properties of objects directly via reflection, circumventing any methods.
 */
class ReflectionPropertyAccessor implements PropertyAccessorInterface
{
    /**
     * @throws ReflectionException
     */
    public function getValueByPropertyPath(?object $target, string $property, string ...$properties)
    {
        if (null === $target) {
            return null;
        }

        $newTarget = $this->getValueWithClass($target, $this->getClass($target), $property);

        // if there are no more paths to follow we return the new target
        if ([] === $properties) {
            return $newTarget;
        }

        return $this->getValueByPropertyPath($newTarget, ...$properties);
    }

    /**
     * @param mixed $target
     */
    public function getValuesByPropertyPath($target, int $depth, array $properties): array
    {
        // if depth is negative then cut of the tail of the given path
        if (0 > $depth) {
            $properties = array_slice($properties, 0, $depth);
            $depth = 0;
        }

        // if there are no more paths to follow we either return the $target itself
        // or the values nested in it, depending on the given $depth
        if ([] === $properties) {
            return $this->restructureNesting($target, $depth);
        }

        // if there are more path to follow we first check if the current target is a list
        // of which we need to access each item individually or if not and we can access
        // the target directly
        $target = $this->restructureNesting($target, 1);

        // for each item (or the target) we follow the remaining paths to the values to return
        $currentPart = array_shift($properties);

        return Iterables::mapFlat(function ($newTarget) use ($currentPart, $properties, $depth): array {
            $newTarget = $this->getValueByPropertyPath($newTarget, $currentPart);
            return [] === $properties
                ? $this->restructureNesting($newTarget, $depth)
                : $this->getValuesByPropertyPath($newTarget, $depth, $properties);
        }, $target);
    }

    public function setValue(object $target, $value, string $propertyName): void
    {
        $reflectionProperty = new ReflectionProperty($this->getClass($target), $propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($target, $value);
    }

    /**
     * @return class-string
     */
    protected function getClass(object $target): string
    {
        return get_class($target);
    }

    /**
     * @param object       $target
     * @param class-string $class
     *
     * @return mixed
     *
     * @throws ReflectionException
     */
    protected function getValueWithClass(object $target, string $class, string $propertyName)
    {
        $reflectionProperty = new ReflectionProperty($class, $propertyName);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($target);
    }

    /**
     * Restructures the given target recursively. The recursion stops when the specified depth
     * is reached or the current target is not iterable.
     *
     * If at any level the current target is non-iterable then the depth will be assumed to be
     * this level, even if the actual `$depth` is given with a greater value.
     *
     * @param mixed $target
     * @param int<0, max> $depth Passing 0 will return the given target wrapped in an array.
     *                   Passing 1 will keep the structure of the given target.
     *                   Passing a value greater 1 will flat the target from the top to the
     *                   bottom, meaning a target with three levels and a depth of 2 will keep the
     *                   third level as it is but flattens the first two levels.
     * @param null|callable(mixed):bool $isIterable Function to determine if the
     *                   current target should be considered iterable and thus flatted.
     *                   Defaults to {@link is_iterable()} if `null` is given.
     *
     * @return list<mixed>
     */
    private function restructureNesting($target, int $depth, callable $isIterable = null): array
    {
        if (null === $isIterable) {
            $isIterable = 'is_iterable';
        }
        if (0 === $depth || !$isIterable($target)) {
            return [$target];
        }

        return Iterables::mapFlat(
            fn ($newTarget): array => $this->restructureNesting($newTarget, $depth - 1),
            is_array($target) ? $target : iterator_to_array($target)
        );
    }
}
