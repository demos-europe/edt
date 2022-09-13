<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * Provides the means to access the property of a target in a reading or writing manner.
 *
 * @template T of object The target type of which a property should be accessed.
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
     * @param T|null $target
     * @return mixed|null
     */
    public function getValueByPropertyPath(?object $target, string $property, string ...$properties);

    /**
     * This method can be used as an alternative to
     * {@link PropertyAccessorInterface::getValueByPropertyPath()} if the accessed path may
     * result in multiple values.
     * If the result is only a single value then that single value will be wrapped
     * into an array and returned in such.
     *
     * If any property in results in a `null` values then `null` will be used as values in the
     * returned array.
     *
     * @param T $target
     *
     * @return list<mixed>
     */
    public function getValuesByPropertyPath($target, int $depth, string $property, string ...$properties): array;

    /**
     * Sets a property values of the given target.
     *
     * @param mixed|null $value
     */
    public function setValue(object $target, $value, string $property): void;
}
