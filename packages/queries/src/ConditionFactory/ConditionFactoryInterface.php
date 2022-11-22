<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathInterface;

/**
 * @template TCondition
 */
interface ConditionFactoryInterface
{
    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyIsNull($properties);

    /**
     * The returned condition will evaluate to `true` if the property denoted by
     * the given path has a value assigned that is present in the given
     * array of values.
     *
     * @param list<mixed> $values
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasAnyOfValues(array $values, $properties);

    /**
     * @param list<mixed> $values
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasNotAnyOfValues(array $values, $properties);

    /**
     * @return TCondition
     */
    public function true();

    /**
     * @param int<0, max> $size
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasSize(int $size, $properties);

    /**
     * @param int<0, max> $size
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasNotSize(int $size, $properties);

    /**
     * @return TCondition
     */
    public function false();

    /**
     * @param non-empty-list<non-empty-string> $leftProperties
     * @param non-empty-list<non-empty-string> $rightProperties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertiesEqual(array $leftProperties, array $rightProperties);

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasStringContainingCaseInsensitiveValue(string $value, $properties);

    /**
     * @param string|int|float|bool $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasValue($value, $properties);

    /**
     * @param string|int|float $min
     * @param string|int|float $max
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyBetweenValuesInclusive($min, $max, $properties);

    /**
     * @param numeric-string|int|float $min
     * @param numeric-string|int|float $max
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyNotBetweenValuesInclusive($min, $max, $properties);

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasStringAsMember(string $value, $properties);

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function valueGreaterThan($value, $properties);

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function valueGreaterEqualsThan($value, $properties);

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function valueSmallerThan($value, $properties);

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function valueSmallerEqualsThan($value, $properties);

    /**
     * @return TCondition
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @throws PathException
     */
    public function propertyStartsWithCaseInsensitive(string $value, $properties);

    /**
     * @return TCondition
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @throws PathException
     */
    public function propertyEndsWithCaseInsensitive(string $value, $properties);

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
     * @param non-empty-list<mixed>            $values
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function allValuesPresentInMemberListProperties(array $values, $properties);

    /**
     * @param string|int|float|bool $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasNotValue($value, $properties);

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyIsNotNull($properties);

    /**
     * The returned condition will match if the property the given path denotes
     * does not contain the given string value as an entry.
     *
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasNotStringAsMember(string $value, $properties);
}
