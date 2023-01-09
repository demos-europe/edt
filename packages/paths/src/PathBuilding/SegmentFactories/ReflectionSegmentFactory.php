<?php

declare(strict_types=1);

namespace EDT\PathBuilding\SegmentFactories;

use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use Exception;
use ReflectionClass;

class ReflectionSegmentFactory implements SegmentFactoryInterface
{
    public function createNextSegment(string $className, PropertyAutoPathInterface $parent, string $parentPropertyName): PropertyPathInterface
    {
        return self::createSegment($className, $parent, $parentPropertyName);
    }

    /**
     * Creates an instance via reflection. The constructor of the given class may be circumvented,
     * which results in an instance that can be used for further path building but may not be
     * suited for other purposes.
     *
     * If no `$constructorArgs` are given the constructor of the given `$className` will be used if
     * it is the default constructor (no constructor implemented) or a constructor that can be
     * invoked without arguments.
     *
     * If `$constructorArgs` are given the method will assume that a constructor exists that is
     * satisfied with the given arguments and invoke it with them.
     *
     * @template TImpl of PropertyAutoPathInterface
     *
     * @param class-string<TImpl> $className
     * @param non-empty-string|null $parentPropertyName
     * @param list<mixed> $constructorArgs
     *
     * @return TImpl
     *
     * @throws Exception
     */
    public static function createSegment(string $className, ?PropertyAutoPathInterface $parent, ?string $parentPropertyName, array $constructorArgs = []): PropertyPathInterface
    {
        $reflectionClass = new ReflectionClass($className);
        if ([] === $constructorArgs) {
            $constructor = $reflectionClass->getConstructor();
            if (null === $constructor || 0 === $constructor->getNumberOfRequiredParameters()) {
                $childPathSegment = $reflectionClass->newInstance();
            } else {
                $childPathSegment = $reflectionClass->newInstanceWithoutConstructor();
            }
        } else {
            $childPathSegment = $reflectionClass->newInstanceArgs($constructorArgs);
        }

        if (null !== $parent) {
            $childPathSegment->setParent($parent);
        }
        if (null !== $parentPropertyName) {
            $childPathSegment->setParentPropertyName($parentPropertyName);
        }

        return $childPathSegment;
    }
}
