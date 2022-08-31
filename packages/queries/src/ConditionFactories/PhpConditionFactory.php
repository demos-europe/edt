<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionFactories;

use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\FunctionInterface;
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

class PhpConditionFactory implements ConditionFactoryInterface
{
    public function true(): FunctionInterface
    {
        return new Value(true);
    }

    public function false(): FunctionInterface
    {
        return new Value(false);
    }

    public function allConditionsApply(FunctionInterface $firstFunction, FunctionInterface ...$additionalFunctions): FunctionInterface
    {
        return new AllEqual(new Value(true), $firstFunction, ...$additionalFunctions);
    }

    public function anyConditionApplies(FunctionInterface $firstFunction, FunctionInterface ...$additionalFunctions): FunctionInterface
    {
        return new AnyTrue($firstFunction, ...$additionalFunctions);
    }

    public function propertiesEqual(array $leftProperties, array $rightProperties): FunctionInterface
    {
        $leftPropertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, ...$leftProperties);
        $rightPropertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, ...$rightProperties);
        return new AllEqual(
            new Property($leftPropertyPath),
            new Property($rightPropertyPath)
        );
    }

    public function propertyBetweenValuesInclusive($min, $max, string $property, string ...$propertyPath): FunctionInterface
    {
        $propertyPathObject = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $property, ...$propertyPath);
        return new BetweenInclusive(
            new Value($min),
            new Value($max),
            new Property($propertyPathObject)
        );
    }

    public function propertyHasAnyOfValues(array $values, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        return new OneOf(
            new Value($values),
            new Property($propertyPath)
        );
    }

    public function propertyHasSize(int $size, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new AllEqual(
            new Size(new Property($propertyPath)),
            new Value($size)
        );
    }

    public function propertyHasStringContainingCaseInsensitiveValue(string $value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        $lowerCaseProperty = new LowerCase(new Property($propertyPathInstance));
        $lowerCaseValue = new LowerCase(new Value($value));
        return new StringContains($lowerCaseProperty, $lowerCaseValue, false);
    }

    public function propertyHasValue($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new AllEqual(
            new Property($propertyPath),
            new Value($value)
        );
    }

    public function propertyIsNull(string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        return new IsNull(new Property($propertyPath));
    }

    public function propertyHasStringAsMember(string $value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new OneOf(
            new Property($propertyPathInstance),
            new Value($value)
        );
    }

    public function valueGreaterThan($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new Greater(
            new Value($value),
            new Property($propertyPathInstance)
        );
    }

    public function valueGreaterEqualsThan($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new GreaterEquals(
            new Value($value),
            new Property($propertyPathInstance)
        );
    }

    public function valueSmallerThan($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new Smaller(
            new Value($value),
            new Property($propertyPathInstance)
        );
    }

    public function valueSmallerEqualsThan($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new SmallerEquals(
            new Value($value),
            new Property($propertyPathInstance)
        );
    }

    public function propertyStartsWithCaseInsensitive(string $value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new StringStartsWith(
            new Value($value),
            new Property($propertyPathInstance),
            false
        );
    }

    public function propertyEndsWithCaseInsensitive(string $value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new StringEndsWith(
            new Value($value),
            new Property($propertyPathInstance),
            false
        );
    }

    public function allValuesPresentInMemberListProperties(array $values, string $property, string ...$properties): FunctionInterface
    {
        $propertyPaths = PropertyPath::createIndexSaltedPaths(count($values), PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        $equalityPairs = array_map(static function ($value, PropertyPathAccessInterface $propertyPath): FunctionInterface {
            return new AllEqual(new Property($propertyPath), new Value($value));
        }, $values, $propertyPaths);

        return new AllEqual(new Value(true), ...$equalityPairs);
    }

    public function propertyHasNotAnyOfValues(array $values, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyHasAnyOfValues($values, $property, ...$properties));
    }

    public function propertyHasNotSize(int $size, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyHasSize($size, $property, ...$properties));
    }

    public function propertyNotBetweenValuesInclusive($min, $max, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyBetweenValuesInclusive($min, $max, $property, ...$properties));
    }

    public function propertyHasNotValue($value, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyHasValue($value, $property, ...$properties));
    }

    public function propertyIsNotNull(string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyIsNull($property, ...$properties));
    }

    public function propertyHasNotStringAsMember(string $value, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyHasStringAsMember($value, $property, ...$properties));
    }
}
