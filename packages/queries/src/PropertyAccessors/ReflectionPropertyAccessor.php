<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyAccessors;

use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Utilities\Iterables;
use ReflectionException;
use ReflectionProperty;
use function get_class;
use function array_slice;

/**
 * Accesses properties of objects directly via reflection, circumventing any methods.
 *
 * @template-implements PropertyAccessorInterface<mixed>
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

    public function getValuesByPropertyPath($target, int $depth, string $property, string ...$properties): array
    {
        array_unshift($properties, $property);

        // if depth is negative then cut of the tail of the given path
        if (0 > $depth) {
            $properties = array_slice($properties, 0, $depth);
            $depth = 0;
        }

        // if there are no more paths to follow we either return the $target itself
        // or the values nested in it, depending on the given $depth
        if ([] === $properties) {
            return Iterables::restructureNesting($target, $depth);
        }

        // if there are more path to follow we first check if the current target is a list
        // of which we need to access each item individually or if not and we can access
        // the target directly
        $target = Iterables::restructureNesting($target, 1);

        // for each item (or the target) we follow the remaining paths to the values to return
        $currentPart = array_shift($properties);

        return Iterables::flat(function ($newTarget) use ($currentPart, $properties, $depth): array {
            $newTarget = $this->getValueByPropertyPath($newTarget, $currentPart);
            return [] === $properties
                ? Iterables::restructureNesting($newTarget, $depth)
                : $this->getValuesByPropertyPath($newTarget, $depth, ...$properties);
        }, $target);
    }

    public function setValue($target, $value, string $propertyName): void
    {
        $reflectionProperty = new ReflectionProperty($this->getClass($target), $propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($target, $value);
    }

    /**
     * @template T
     * @param T $target
     * @return class-string<T>
     */
    protected function getClass(object $target): string
    {
        return get_class($target);
    }

    /**
     * @template T of object
     * @param T $target
     * @param class-string<T> $class
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
}
