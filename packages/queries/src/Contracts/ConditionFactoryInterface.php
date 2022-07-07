<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

interface ConditionFactoryInterface
{
    /**
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyIsNull(string $property, string ...$properties): FunctionInterface;

    /**
     * The returned condition will evaluate to `true` if the property denoted by
     * the given path has a value assigned that is present in the given
     * array of values.
     *
     * @param array<int,mixed> $values
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyHasAnyOfValues(array $values, string $property, string ...$properties): FunctionInterface;

    /**
     * @param array<int,mixed> $values
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyHasNotAnyOfValues(array $values, string $property, string ...$properties): FunctionInterface;

    /**
     * @return FunctionInterface<bool>
     */
    public function true(): FunctionInterface;

    /**
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyHasSize(int $size, string $property, string ...$properties): FunctionInterface;

    /**
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyHasNotSize(int $size, string $property, string ...$properties): FunctionInterface;

    /**
     * @return FunctionInterface<bool>
     */
    public function false(): FunctionInterface;

    /**
     * @param string[] $leftProperties
     * @param string[] $rightProperties
     *
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertiesEqual(array $leftProperties, array $rightProperties): FunctionInterface;

    /**
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyHasStringContainingCaseInsensitiveValue(string $value, string $property, string ...$properties): FunctionInterface;

    /**
     * @param FunctionInterface<bool> $firstFunction
     * @param FunctionInterface<bool> ...$additionalFunctions
     *
     * @return FunctionInterface<bool>
     */
    public function allConditionsApply(FunctionInterface $firstFunction, FunctionInterface ...$additionalFunctions): FunctionInterface;

    /**
     * @param mixed $value
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyHasValue($value, string $property, string ...$properties): FunctionInterface;

    /**
     * @param mixed $min
     * @param mixed $max
     *
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyBetweenValuesInclusive($min, $max, string $property, string ...$propertyPath): FunctionInterface;

    /**
     * @param mixed $min
     * @param mixed $max
     *
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyNotBetweenValuesInclusive($min, $max, string $property, string ...$propertyPath): FunctionInterface;

    /**
     * @param FunctionInterface<bool> $firstFunction
     * @param FunctionInterface<bool> ...$additionalFunctions
     *
     * @return FunctionInterface<bool>
     */
    public function anyConditionApplies(FunctionInterface $firstFunction, FunctionInterface ...$additionalFunctions): FunctionInterface;

    /**
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyHasStringAsMember(string $value, string $property, string ...$properties): FunctionInterface;

    /**
     * @param mixed $value
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function valueGreaterThan($value, string $property, string ...$path): FunctionInterface;

    /**
     * @param mixed $value
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function valueGreaterEqualsThan($value, string $property, string ...$path): FunctionInterface;

    /**
     * @param mixed $value
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function valueSmallerThan($value, string $property, string ...$path): FunctionInterface;

    /**
     * @param mixed $value
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function valueSmallerEqualsThan($value, string $property, string ...$path): FunctionInterface;

    /**
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyStartsWithCaseInsensitive(string $value, string $property, string ...$path): FunctionInterface;

    /**
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyEndsWithCaseInsensitive(string $value, string $property, string ...$path): FunctionInterface;

    /**
     * It is expected that the given property path contains a to-many relationship and thus will
     * lead to multiple values. The returned condition will evaluate to true if the given values
     * can all be found in the set of values.
     *
     * For example: assume an author with a to-many relationship to books. Each book has
     * exactly one title. If you want a condition to match only authors that have written
     * both a book with the title `A` and another one with the title `B`, then you need to
     * use this method with the parameters `['A', 'B']` (the required titles as array),
     * and `'books'` and `'title'` (the path to the titles).
     *
     * @param array<int,mixed> $values Must not be empty.
     *
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function allValuesPresentInMemberListProperties(array $values, string $property, string ...$properties): FunctionInterface;

    /**
     * @param mixed $value
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyHasNotValue($value, string $property, string ...$properties): FunctionInterface;

    /**
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyIsNotNull(string $property, string ...$properties): FunctionInterface;

    /**
     * The returned condition will match if the property the given path denotes
     * does not contain the given string value as an entry.
     *
     * @return FunctionInterface<bool>
     *
     * @throws PathException
     */
    public function propertyHasNotStringAsMember(string $value, string $property, string ...$properties): FunctionInterface;
}
