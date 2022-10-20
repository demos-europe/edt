<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionFactories;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\ConditionFactory\PathsBasedConditionGroupFactoryInterface;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Functions\AllEqual;
use EDT\Querying\Functions\AnyTrue;
use EDT\Querying\Functions\BetweenInclusive;
use EDT\Querying\Functions\StringContains;
use EDT\Querying\Functions\Greater;
use EDT\Querying\Functions\GreaterEquals;
use EDT\Querying\Functions\OneOf;
use EDT\Querying\Functions\IsNull;
use EDT\Querying\Functions\LowerCase;
use EDT\Querying\Functions\Property;
use EDT\Querying\Functions\Size;
use EDT\Querying\Functions\Smaller;
use EDT\Querying\Functions\SmallerEquals;
use EDT\Querying\Functions\StringEndsWith;
use EDT\Querying\Functions\StringStartsWith;
use EDT\Querying\Functions\Value;
use EDT\Querying\Functions\InvertedBoolean;
use EDT\Querying\PropertyPaths\PropertyPath;
use function count;

/**
 * @template-implements PathsBasedConditionFactoryInterface<FunctionInterface<bool>>
 * @template-implements PathsBasedConditionGroupFactoryInterface<FunctionInterface<bool>>
 */
class PhpConditionFactory implements PathsBasedConditionFactoryInterface, PathsBasedConditionGroupFactoryInterface
{
    public function true(): PathsBasedInterface
    {
        return new Value(true);
    }

    public function false(): PathsBasedInterface
    {
        return new Value(false);
    }

    /**
     * @param FunctionInterface<bool> $firstCondition
     * @param FunctionInterface<bool> ...$additionalConditions
     *
     * @return FunctionInterface<bool>
     */
    public function allConditionsApply($firstCondition, ...$additionalConditions): PathsBasedInterface
    {
        return new AllEqual(new Value(true), $firstCondition, ...$additionalConditions);
    }

    /**
     * @param FunctionInterface<bool> $firstCondition
     * @param FunctionInterface<bool> ...$additionalConditions
     *
     * @return FunctionInterface<bool>
     */
    public function anyConditionApplies($firstCondition, ...$additionalConditions): PathsBasedInterface
    {
        return new AnyTrue($firstCondition, ...$additionalConditions);
    }

    public function propertiesEqual(array $leftProperties, array $rightProperties): PathsBasedInterface
    {
        $leftPropertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, ...$leftProperties);
        $rightPropertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, ...$rightProperties);
        return new AllEqual(
            new Property($leftPropertyPath),
            new Property($rightPropertyPath)
        );
    }

    public function propertyBetweenValuesInclusive($min, $max, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPathObject = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        return new BetweenInclusive(
            new Value($min),
            new Value($max),
            new Property($propertyPathObject)
        );
    }

    public function propertyHasAnyOfValues(array $values, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        return new OneOf(
            new Value($values),
            new Property($propertyPath)
        );
    }

    public function propertyHasSize(int $size, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new AllEqual(
            new Size(new Property($propertyPath)),
            new Value($size)
        );
    }

    public function propertyHasStringContainingCaseInsensitiveValue(string $value, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        $lowerCaseProperty = new LowerCase(new Property($propertyPathInstance));
        $lowerCaseValue = new LowerCase(new Value($value));
        return new StringContains($lowerCaseProperty, $lowerCaseValue, false);
    }

    public function propertyHasValue($value, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new AllEqual(
            new Property($propertyPath),
            new Value($value)
        );
    }

    public function propertyIsNull(string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        return new IsNull(new Property($propertyPath));
    }

    public function propertyHasStringAsMember(string $value, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new OneOf(
            new Property($propertyPathInstance),
            new Value($value)
        );
    }

    public function valueGreaterThan($value, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new Greater(
            new Value($value),
            new Property($propertyPathInstance)
        );
    }

    public function valueGreaterEqualsThan($value, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new GreaterEquals(
            new Value($value),
            new Property($propertyPathInstance)
        );
    }

    public function valueSmallerThan($value, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new Smaller(
            new Value($value),
            new Property($propertyPathInstance)
        );
    }

    public function valueSmallerEqualsThan($value, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new SmallerEquals(
            new Value($value),
            new Property($propertyPathInstance)
        );
    }

    public function propertyStartsWithCaseInsensitive(string $value, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new StringStartsWith(
            new Value($value),
            new Property($propertyPathInstance),
            false
        );
    }

    public function propertyEndsWithCaseInsensitive(string $value, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new StringEndsWith(
            new Value($value),
            new Property($propertyPathInstance),
            false
        );
    }

    public function allValuesPresentInMemberListProperties(array $values, string $property, string ...$properties): PathsBasedInterface
    {
        $propertyPaths = PropertyPath::createIndexSaltedPaths(count($values), PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        $equalityPairs = array_map(static function ($value, PropertyPathAccessInterface $propertyPath): FunctionInterface {
            return new AllEqual(new Property($propertyPath), new Value($value));
        }, $values, $propertyPaths);

        return new AllEqual(new Value(true), ...$equalityPairs);
    }

    public function propertyHasNotAnyOfValues(array $values, string $property, string ...$properties): PathsBasedInterface
    {
        return new InvertedBoolean($this->propertyHasAnyOfValues($values, $property, ...$properties));
    }

    public function propertyHasNotSize(int $size, string $property, string ...$properties): PathsBasedInterface
    {
        return new InvertedBoolean($this->propertyHasSize($size, $property, ...$properties));
    }

    public function propertyNotBetweenValuesInclusive($min, $max, string $property, string ...$properties): PathsBasedInterface
    {
        return new InvertedBoolean($this->propertyBetweenValuesInclusive($min, $max, $property, ...$properties));
    }

    public function propertyHasNotValue($value, string $property, string ...$properties): PathsBasedInterface
    {
        return new InvertedBoolean($this->propertyHasValue($value, $property, ...$properties));
    }

    public function propertyIsNotNull(string $property, string ...$properties): PathsBasedInterface
    {
        return new InvertedBoolean($this->propertyIsNull($property, ...$properties));
    }

    public function propertyHasNotStringAsMember(string $value, string $property, string ...$properties): PathsBasedInterface
    {
        return new InvertedBoolean($this->propertyHasStringAsMember($value, $property, ...$properties));
    }
}
