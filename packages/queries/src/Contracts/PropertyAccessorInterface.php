<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * Provides the means to access the property of a target in a reading or writing manner.
 */
interface PropertyAccessorInterface
{
    /**
     * Will get a single value following the given path. To get multiple values refer to
     * {@link PropertyAccessorInterface::getValuesByPropertyPath()}.
     *
     * The final target of the path can be any type (primitive, object, array, ...) but the
     * preceding path parts must be single values (i.e. not lists, not arrays, not collections).
     *
     * For example passing a `Book` object as $target and `'author', 'name'` as properties
     * is fine **if** your book always has a single author. It is also ok if that author has
     * a list of names stored in its `name` property, because the `name` property is the last
     * part in the alias path.
     * However, in case your `Book` has multiple authors, and you use a path like
     * `'authors', 'name'` you may get errors or undesired/unexpected behavior.
     *
     * If any property in results in a `null` value then `null` will be returned.
     *
     * @param non-empty-string $property TODO: refactor to non-empty-list<non-empty-string>
     * @param non-empty-string ...$properties
     */
    public function getValueByPropertyPath(?object $target, string $property, string ...$properties): mixed;

    /**
     * This method can be used as an alternative to
     * {@link PropertyAccessorInterface::getValueByPropertyPath()} if the accessed path may
     * result in multiple values.
     * If the result is only a single value then that single value will be wrapped
     * into an array and returned in such.
     *
     * If any property results in a `null` values then `null` will be used as values in the
     * returned array.
     *
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return list<mixed>
     */
    public function getValuesByPropertyPath(object $target, int $depth, array $properties): array;

    /**
     * Sets a property values of the given target.
     *
     * @param non-empty-string $propertyName
     */
    public function setValue(object $target, mixed $value, string $propertyName): void;
}
