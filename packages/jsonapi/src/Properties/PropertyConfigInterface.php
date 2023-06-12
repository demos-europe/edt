<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathInterface;

/**
 * @template TEntity of object
 * @template TValue
 * @template TCondition of PathsBasedInterface
 */
interface PropertyConfigInterface
{
    /**
     * Set an alias to follow when this property is accessed.
     *
     * Beside {@link PropertyBuilder::readable() custom read functions},
     * aliases are another and the preferred way to slightly deviate from the schema of the backing
     * entity class when exposing properties. Their advantage over custom read functions is that
     * conditions and sort methods that are using aliases can still be executed in the database,
     * which is not the case for custom read functions as they require data from the database being
     * loaded into PHP.
     *
     * An alias can redirect to a property in the backing entity or to the property of a different
     * entity, if there is a connection from the first entity to the latter one.
     *
     * Simple example: if you define a property `bookTitle` for your resource but the actual entity property
     * is stored in `title`, then you can pass `title` as `$aliasedPath`.
     *
     * Advanced example: if you define an (alias) attribute `authorName` for your `Book`
     * resource type, you can redirect it to `['author', 'name']` if `Book` has a relationship to
     * `Author` named `book` that is present in the entity backing the `Book` resource type and
     * `Author` has a property `name` that is present in the entity backing the `Author` resource
     * type.
     *
     * Note that the path in the alias will not be validated against the type schemas.
     * With the example above that means that it does not matter if the `author` property is a
     * readable property in the `BookType` or if the `name` property is a readable property in
     * a potentially existing `AuthorType`. The `['author', 'name']` just needs to be a valid
     * path into the backing `Book` and `Author` entities.
     *
     * @param PropertyPathInterface $aliasedPath all segments must have a corresponding property in the backing entity
     *
     * @return $this
     */
    public function aliasedPath(PropertyPathInterface $aliasedPath): self;

    /**
     * Mark this property as usable when filtering resources. It can then be used to filter
     * the resource they belong to as well as other resources when used in a path.
     *
     * E.g. when a user has access to `Book` and `Author` resource types (and `Book` has a
     * to-one relationship to `Author` while `Author` has a to-many relationship to `Book`), then
     * setting `fullName` in the `Author` type to filterable allows not only to filter `Author`
     * resources by their name but also to filter `Book` resources by the name of their author, if
     * the relationship to `Author` is set as filterable too.
     *
     * However, filtering `Author` resources by the title of their books will not work, because
     * of the to-many relationship it would not be clear which `Book` resource to use from an
     * author when testing it.
     *
     * E.g. if you enable a `price` property in a `Book` resource for filtering then you
     * can not only filter books by their price but also authors by the price of the
     * books they have written (assuming the necessary relationship from authors to
     * books is defined and the resources'
     * {@link ExposableRelationshipTypeInterface::isExposedAsRelationship()} returns `true`).
     *
     * @return $this
     */
    public function filterable(): self;

    /**
     * Mark this property as usable when sorting resources. It can then be used to sort
     * the resources they belong to as well as other resources when used in a path.
     *
     * E.g. when a user has access to `Book` and `Author` resource types (and `Book` has a
     * to-one relationship to `Author` while `Author` has a to-many relationship to `Book`), then
     * setting `fullName` in the `Author` type to sortable allows not only to sort `Author`
     * resources by their name but also to sort `Book` resources by the name of their author, if
     * the relationship to `Author` is set as sortable too.
     *
     * However, sorting `Author` resources by the title of their books will not work, because
     * of the to-many relationship it would not be clear which `Book` resource to use from an
     * author when comparing two `Author` resources.
     *
     * @see https://jsonapi.org/format/#fetching-sorting
     *
     * @return $this
     */
    public function sortable(): self;

    /**
     * Mark this property as readable, i.e. allow its value to be read.
     *
     * When used on an attribute the actual attribute value can be accessed. When used on a
     * relationship the relationship reference can be accessed, but to access the properties
     * of the relationship these properties must be set as readable too.
     *
     * Using `readable()` is the same as using `readable(false)`, meaning the property's value
     * can be accessed but will only be present in the JSON:API response when a
     * [sparse fieldset](https://jsonapi.org/format/#fetching-sparse-fieldsets) request was used
     * requesting that property. To automatically have the value present in the JSON:API response
     * when no sparse fieldset request is used, `true` must be used as `$defaultField` parameter.
     *
     * By passing a `$customRead` callable you can override the default behavior when the
     * property is read, e.g. when it is written into a JSON:API response. Normally the system
     * will get the value from an object by looking for a property within it and directly
     * reading the value from it, circumventing any getter method. If an alias is set, it
     * will simply redirect the access through the different properties until the end of the
     * alias path is reached.
     *
     * Directly accessing the property is a good default behavior because it is consistent with
     * the behavior for Doctrine entities when they are filtered or sorted via their properties,
     * because there are no getters for them in the database.
     *
     * By passing the `$customRead` callable here the value will not be read directly from
     * the property anymore but from the callable instead, by passing the object as parameter
     * when calling the callable.
     *
     * This may introduce unintended inconsistencies: if the `$customRead` callable returns
     * a different value than the one stored in the property, then sorting and filtering
     * will be executed on a different value. It is not possible to set a custom
     * read callable for sorting/filtering due to compatibility requirements with Doctrine
     * as explained above.
     *
     * To avoid unintended inconsistencies you can **not** do the following with a property for
     * which a custom read callable was set (an exception will be thrown when the property is used):
     *
     * * set it as sortable
     * * set it as filterable
     * * set an alias
     *
     * If you accept the risk of inconsistencies you can set a custom read callback while also enabling
     * filtering or sorting, in which case the `$customReadCallback` will be used when reading the value of the property,
     * and the value stored in the property will be used when filtering or sorting. The interaction
     * with aliases is undefined.
     *
     * @param bool $defaultField the field is to be returned in responses by default
     * @param null|callable(TEntity): TValue $customReadCallback to be set if this property needs special handling when read
     *
     * @return $this
     *
     * @see https://jsonapi.org/format/#fetching-sparse-fieldsets JSON:API sparse fieldsets
     */
    public function readable(bool $defaultField = false, callable $customReadCallback = null): self;

    /**
     * @param list<TCondition> $entityConditions
     * @param null|callable(TEntity, TValue): bool $customUpdateCallback
     * @param list<TCondition> $relationshipConditions
     *
     * @return $this
     */
    public function updatable(
        array $entityConditions,
        ?callable $customUpdateCallback,
        array $relationshipConditions,
    ): self;

    /**
     * Mark the property as initializable when creating a resource.
     *
     * By default, properties marked as initializable are required to be present in a request when
     * a resource is created. You can change that by setting the `$optional` parameter to `true`.
     *
     * @param list<TCondition> $relationshipConditions
     * @param null|callable(TEntity, TValue): bool $customInitCallback
     *
     * @return $this
     */
    public function initializable(bool $optional = false, array $relationshipConditions = [], callable $customInitCallback = null): self;
}
