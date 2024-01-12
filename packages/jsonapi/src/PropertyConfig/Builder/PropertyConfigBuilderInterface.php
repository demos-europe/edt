<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PropertyPathInterface;

interface PropertyConfigBuilderInterface
{
    /**
     * Set an alias to follow when this property is accessed.
     *
     * Beside {@link AbstractPropertyConfigBuilder::readable() custom read functions},
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
     * @param non-empty-list<non-empty-string>|PropertyPathInterface $aliasedPath all segments must have a corresponding property in the backing entity
     *
     * @return $this
     */
    public function aliasedPath(array|PropertyPathInterface $aliasedPath): self;

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
     * Filtering `Author` resources by the title of their books will work too, even though there may be multiple book
     * titles for a single author. For example in case of the equals operator it is sufficient if the author is
     * connected to the book title value at least once to be considered a match.
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
}
