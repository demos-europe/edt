<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Querying\Utilities\Iterables;
use function count;
use function in_array;
use function Safe\class_uses;
use function Safe\class_parents;

class TraitEvaluator
{
    /**
     * Get all traits that are in use in the given class or in any parent (extended) class.
     *
     * Only traits directly used in the given class or directly used in one of the parents
     * are returned. Traits within traits will **not** be returned.
     *
     * @param class-string $class
     *
     * @return list<string> may be empty
     */
    public function getAllClassTraits(string $class): array
    {
        $classes = $this->getAllParentClasses($class);
        array_unshift($classes, $class);

        $traitsInClasses = array_map(
            static fn (string $class): array => array_values(class_uses($class)),
            $classes
        );

        return array_merge(...$traitsInClasses);
    }

    /**
     * Get all classes the given class extends from and all interfaces that are implemented by the
     * given class or any of its parents or any of the interfaces.
     *
     * The classes/interfaces in the returned array are sorted by their "nearness" to the given class.
     *
     * Classes take preference and are sorted deterministically, meaning when a class A extends a class B and implements an interface D, then B will be returned
     * before D. If B extends C then C will be returned between B and D.
     *
     * Interfaces are sorted after all classes with the attempt to sort interfaces closer to the given
     * class before inferfaces further away from it, but due to possible multi-inheritance no guarantees are made.
     *
     * If an interfaces is used by multiple classes/interfaces it will only be returned once in the array.
     *
     * @param class-string $class
     *
     * @return list<class-string>
     */
    public function getAllParents(string $class): array
    {
        $parents = $this->getAllParentClasses($class);
        $nestedInterfaces = array_map('Safe\class_implements', array_reverse($parents));

        $interfaces = [] === $nestedInterfaces ? [] : array_merge(...$nestedInterfaces);

        return array_merge($parents, array_values($interfaces));
    }

    /**
     * @param class-string $class
     *
     * @return list<class-string>
     */
    private function getAllParentClasses(string $class): array
    {
        return array_values(class_parents($class));
    }

    /**
     * Get all traits that are in use in the given trait or in any of the traits it uses
     * (recursive with endless depth).
     *
     * The given trait will be part of the result.
     *
     * @param non-empty-string $trait
     * @return non-empty-list<non-empty-string>
     */
    public function getAllNestedTraits(string $trait): array
    {
        $traits = class_uses($trait);
        if (0 === count($traits)) {
            return [$trait];
        }
        $traits = Iterables::mapFlat([$this, 'getAllNestedTraits'], $traits);
        $traits[] = $trait;

        return $traits;
    }

    /**
     * Checks if the given class uses the given trait. The method will take
     * parent classes and nested traits into consideration.
     *
     * @param class-string $class
     * @param non-empty-string $trait
     */
    public function isClassUsingTrait(string $class, string $trait): bool
    {
        $traits = $this->getAllClassTraits($class);
        if (0 === count($traits)) {
            return false;
        }

        $traits = Iterables::mapFlat([$this, 'getAllNestedTraits'], $traits);
        $traits = array_unique($traits);

        return in_array($trait, $traits, true);
    }
}
