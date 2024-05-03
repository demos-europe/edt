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
    public function propertyIsNull(string|array|PropertyPathInterface $properties);

    /**
     * The returned condition will evaluate to `true` if the property denoted by
     * the given path has a value assigned that is present in the given
     * array of values.
     *
     * @param non-empty-list<mixed> $values
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasAnyOfValues(array $values, string|array|PropertyPathInterface $properties);

    /**
     * @param non-empty-list<mixed> $values
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasNotAnyOfValues(array $values, string|array|PropertyPathInterface $properties);

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
    public function propertyHasSize(int $size, string|array|PropertyPathInterface $properties);

    /**
     * @param int<0, max> $size
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasNotSize(int $size, string|array|PropertyPathInterface $properties);

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
    public function propertyHasStringContainingCaseInsensitiveValue(string $value, string|array|PropertyPathInterface $properties);

    /**
     * Returns a condition that matches an entity, if it contains the given value in the given property path.
     *
     * For example if you want to match an author that has written any book with the title `'A'` you would call this
     * method with `'A'` as value and `['books', 'title']` as path.
     *
     * However, note that with this method it is not possible to match a single author that has written both a book with
     * the title `A` and another book with the title `B`. E.g. it is not sufficient to simply create a group with
     * {@link ConditionGroupFactoryInterface::allConditionsApply()}, containing two
     * {@link ConditionFactoryInterface::propertyHasValue()} conditions, one comparing `books.title`
     * with `A` and the other one comparing `book.title` with `B`. This would match no author, as it requires that an
     * author has written at least one book that has the title `A` and `B` at the same time, which is not possible in
     * this example entity model. For such a case, you need to use {@link allValuesPresentInMemberListProperties()}.
     *
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasValue(string|int|float|bool $value, string|array|PropertyPathInterface $properties);

    /**
     * @param numeric-string|int|float $min
     * @param numeric-string|int|float $max
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyBetweenValuesInclusive(
        string|int|float $min,
        string|int|float $max,
        string|array|PropertyPathInterface $properties
    );

    /**
     * @param numeric-string|int|float $min
     * @param numeric-string|int|float $max
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyNotBetweenValuesInclusive(
        string|int|float $min,
        string|int|float $max,
        string|array|PropertyPathInterface $properties
    );

    /**
     * Check if an entity is part of a to-many collection by providing the entity ID.
     *
     * E.g. if you wanted to know if an author with a to-many relationship to books has a reference to a specific book,
     * you would provide the ID of that specific book as value and `['books']` as path.
     *
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasStringAsMember(string $value, string|array|PropertyPathInterface $properties);

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function valueGreaterThan(string|int|float $value, string|array|PropertyPathInterface $properties);

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function valueGreaterEqualsThan(string|int|float $value, string|array|PropertyPathInterface $properties);

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function valueSmallerThan(string|int|float $value, string|array|PropertyPathInterface$properties);

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function valueSmallerEqualsThan(string|int|float $value, string|array|PropertyPathInterface $properties);

    /**
     * @return TCondition
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @throws PathException
     */
    public function propertyStartsWithCaseInsensitive(string $value, string|array|PropertyPathInterface $properties);

    /**
     * @return TCondition
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @throws PathException
     */
    public function propertyEndsWithCaseInsensitive(string $value, string|array|PropertyPathInterface $properties);

    /**
     * It is expected that the given property path contains at least one to-many relationship and thus will
     * lead to multiple values. The returned condition will evaluate to true if the given values
     * can all be found in the set of values the path leads to.
     *
     * For example: assume an author with a to-many relationship to books. Each book has
     * exactly one title. If you want a condition to match only authors that have written
     * both a book with the title `A` and another book with the title `B`, then you need to
     * use this method with the parameters `['A', 'B']` (the required titles as array),
     * and `['books', 'title']` as property path (the path to the titles).
     *
     * A more detailed explanation in the context of relational databases can be found in
     * {@link \EDT\DqlQuerying\ConditionFactories\DqlConditionFactory::allValuesPresentInMemberListProperties}.
     *
     * @param non-empty-list<mixed> $values
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function allValuesPresentInMemberListProperties(array $values, string|array|PropertyPathInterface $properties);

    /**
     * Creates a condition that matches all entities that have a value set in the target of the
     * given path, that is not equal to the given value.
     *
     * While the result is intuitive when the path follows to-one relationships only, it becomes more
     * complex for to-many relationships.
     *
     * Suppose `Book` entities are fetched, with each book
     * having many authors, then `propertyHasNotValue('Bob', ['authors', 'firstName'])`
     * will correctly match a book written by Bob and some other person. Such a book matches the
     * condition, because there _is_ an author with a name that is _not_ `Bob`.
     *
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasNotValue(
        string|int|float|bool $value,
        string|array|PropertyPathInterface $properties
    );

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyIsNotNull(string|array|PropertyPathInterface $properties);

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
    public function propertyHasNotStringAsMember(string $value, string|array|PropertyPathInterface $properties);
}
