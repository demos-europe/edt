<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * @template T of FunctionInterface<bool>
 */
interface ConditionFactoryInterface
{
    /**
     * @return T
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
     * @return T
     *
     * @throws PathException
     */
    public function propertyHasAnyOfValues(array $values, string $property, string ...$properties): FunctionInterface;

    /**
     * @param array<int,mixed> $values
     * @return T
     *
     * @throws PathException
     */
    public function propertyHasNotAnyOfValues(array $values, string $property, string ...$properties): FunctionInterface;

    /**
     * @return T
     */
    public function true(): FunctionInterface;

    /**
     * @return T
     *
     * @throws PathException
     */
    public function propertyHasSize(int $size, string $property, string ...$properties): FunctionInterface;

    /**
     * @return T
     *
     * @throws PathException
     */
    public function propertyHasNotSize(int $size, string $property, string ...$properties): FunctionInterface;

    /**
     * @return T
     */
    public function false(): FunctionInterface;

    /**
     * @param string[] $leftProperties
     * @param string[] $rightProperties
     *
     * @return T
     *
     * @throws PathException
     */
    public function propertiesEqual(array $leftProperties, array $rightProperties): FunctionInterface;

    /**
     * @return T
     *
     * @throws PathException
     */
    public function propertyHasStringContainingCaseInsensitiveValue(string $value, string $property, string ...$properties): FunctionInterface;

    /**
     * @param T $firstFunction
     * @param T ...$additionalFunctions
     *
     * @return T
     */
    public function allConditionsApply(FunctionInterface $firstFunction, FunctionInterface ...$additionalFunctions): FunctionInterface;

    /**
     * @param mixed $value
     * @return T
     *
     * @throws PathException
     */
    public function propertyHasValue($value, string $property, string ...$properties): FunctionInterface;

    /**
     * @param mixed $min
     * @param mixed $max
     *
     * @return T
     *
     * @throws PathException
     */
    public function propertyBetweenValuesInclusive($min, $max, string $property, string ...$propertyPath): FunctionInterface;

    /**
     * @param mixed $min
     * @param mixed $max
     *
     * @return T
     *
     * @throws PathException
     */
    public function propertyNotBetweenValuesInclusive($min, $max, string $property, string ...$propertyPath): FunctionInterface;

    /**
     * @param T $firstFunction
     * @param T ...$additionalFunctions
     *
     * @return T
     */
    public function anyConditionApplies(FunctionInterface $firstFunction, FunctionInterface ...$additionalFunctions): FunctionInterface;

    /**
     * @return T
     *
     * @throws PathException
     */
    public function propertyHasStringAsMember(string $value, string $property, string ...$properties): FunctionInterface;

    /**
     * @param mixed $value
     * @return T
     *
     * @throws PathException
     */
    public function valueGreaterThan($value, string $property, string ...$path): FunctionInterface;

    /**
     * @param mixed $value
     * @return T
     *
     * @throws PathException
     */
    public function valueGreaterEqualsThan($value, string $property, string ...$path): FunctionInterface;

    /**
     * @param mixed $value
     * @return T
     *
     * @throws PathException
     */
    public function valueSmallerThan($value, string $property, string ...$path): FunctionInterface;

    /**
     * @param mixed $value
     * @return T
     *
     * @throws PathException
     */
    public function valueSmallerEqualsThan($value, string $property, string ...$path): FunctionInterface;

    /**
     * @return T
     *
     * @throws PathException
     */
    public function propertyStartsWithCaseInsensitive(string $value, string $property, string ...$path): FunctionInterface;

    /**
     * @return T
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
     * @param non-empty-array<int,mixed> $values
     *
     * @return T
     *
     * @throws PathException
     */
    public function allValuesPresentInMemberListProperties(array $values, string $property, string ...$properties): FunctionInterface;

    /**
     * @param mixed $value
     * @return T
     *
     * @throws PathException
     */
    public function propertyHasNotValue($value, string $property, string ...$properties): FunctionInterface;

    /**
     * @return T
     *
     * @throws PathException
     */
    public function propertyIsNotNull(string $property, string ...$properties): FunctionInterface;

    /**
     * The returned condition will match if the property the given path denotes
     * does not contain the given string value as an entry.
     *
     * @return T
     *
     * @throws PathException
     */
    public function propertyHasNotStringAsMember(string $value, string $property, string ...$properties): FunctionInterface;
}
