<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Querying\Utilities\Iterables;
use InvalidArgumentException;
use Safe\Exceptions\SplException;
use Webmozart\Assert\Assert;
use function count;
use function Safe\class_uses;
use function Safe\class_parents;

/**
 * TODO (#149): prevent endless loops (traits using each other directly or indirectly)
 */
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
     * @return list<trait-string>
     * @throws SplException
     */
    public function getAllClassTraits(string $class): array
    {
        $classes = $this->getAllParentClasses($class);
        array_unshift($classes, $class);

        $traitsInClasses = array_map(
            static fn (string $class): array => array_values(class_uses($class)),
            $classes
        );

        $traits = array_merge(...$traitsInClasses);
        Assert::allStringNotEmpty($traits);

        return array_map([$this, 'assertTraitExists'], $traits);
    }

    /**
     * @param non-empty-string $trait
     * @return trait-string
     */
    protected function assertTraitExists(string $trait): string
    {
        if (!trait_exists($trait)) {
            throw new InvalidArgumentException("Trait `$trait` not found");
        }

        return $trait;
    }

    /**
     * Get all classes the given class extends from and all interfaces that are implemented by the
     * given class or any of its parents or any of the interfaces.
     *
     * The classes/interfaces in the returned array are sorted by their "nearness" to the given class.
     *
     * Classes take preference and are sorted deterministically, meaning when a class A extends a
     * class B and implements an interface D, then B will be returned
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
        Assert::allInterfaceExists($interfaces);

        return array_merge($parents, array_values($interfaces));
    }

    /**
     * @param class-string $class
     *
     * @return list<class-string>
     * @throws SplException
     */
    protected function getAllParentClasses(string $class): array
    {
        $parents = array_values(class_parents($class));
        Assert::allClassExists($parents);

        return $parents;
    }

    /**
     * Get all traits that are in use in the given trait or in any of the traits it uses
     * (recursive with endless depth).
     *
     * The given trait will be part of the result.
     *
     * @param trait-string $trait
     *
     * @return non-empty-list<trait-string>
     *
     * @throws SplException
     */
    public function getWithAllNestedTraits(string $trait): array
    {
        $traits = class_uses($trait);
        if (0 === count($traits)) {
            return [$trait];
        }
        $traits = array_map([$this, 'assertTraitExists'], $traits);
        $traits = Iterables::mapFlat([$this, 'getWithAllNestedTraits'], $traits);
        $traits[] = $trait;

        Assert::allStringNotEmpty($traits);

        return $traits;
    }

    /**
     * Checks if the given class uses all the given traits. The method will take
     * parent classes and nested traits into consideration.
     *
     * @param class-string $targetClass
     * @param list<trait-string> $requiredTraits
     *
     * @throws SplException
     */
    public function isClassUsingAllTraits(string $targetClass, array $requiredTraits): bool
    {
        if (0 === count($requiredTraits)) {
            return true;
        }

        $usedTraits = $this->getAllClassTraits($targetClass);
        if (0 === count($usedTraits)) {
            return false;
        }

        $usedAndNestedTraits = Iterables::mapFlat([$this, 'getWithAllNestedTraits'], $usedTraits);
        $missingTraits = array_diff($requiredTraits, array_unique($usedAndNestedTraits));

        return 0 === count($missingTraits);
    }
}
