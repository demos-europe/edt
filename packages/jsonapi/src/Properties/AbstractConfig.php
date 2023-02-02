<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\Types\AliasableTypeInterface;
use EDT\Wrapping\Contracts\Types\CreatableTypeInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\AbstractReadability;
use EDT\Wrapping\Properties\Initializability;
use EDT\Wrapping\Properties\AbstractUpdatability;

/**
 * Set up a specific property for accesses via the generic JSON:API implementation.
 *
 * * {@link AbstractConfig::$filtering filtering via property values}
 * * {@link AbstractConfig::$sorting sorting via property values}
 * * {@link AbstractConfig::$readability reading of actual property values}
 * * {@link AbstractConfig::$initializable creating of resources with property values}
 *
 * You can also mark the property as an alias by setting {@link PropertyBuilder::aliasedPath()}.
 * This will result in all accesses mentioned above expecting that the path segments having
 * corresponding properties in the backend entities.
 *
 * Note that the resource type itself must return `true` in
 * {@link ExposablePrimaryResourceTypeInterface::isExposedAsPrimaryResource()} to be accessed
 * directly via the JSON:API or in
 * {@link ExposableRelationshipTypeInterface::isExposedAsRelationship()} to be usable as
 * relationship via the JSON:API.
 *
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 * @template TReadability of AbstractReadability
 * @template TUpdatability of AbstractUpdatability
 */
abstract class AbstractConfig
{
    protected bool $filtering = false;

    protected bool $sorting = false;

    /**
     * @var non-empty-list<non-empty-string>|null
     */
    protected ?array $aliasedPath = null;

    /**
     * @var TReadability|null
     */
    protected ?AbstractReadability $readability = null;

    /**
     * @var Initializability<TCondition>|null
     */
    private ?Initializability $initializable = null;

    /**
     * @var TUpdatability|null
     */
    protected ?AbstractUpdatability $updatability = null;

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
     *
     * @throws ResourcePropertyConfigException
     */
    public function enableFiltering(): self
    {
        $this->assertNullOrImplements(FilterableTypeInterface::class, 'usable to filter');

        $this->filtering = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableFiltering(): self
    {
        $this->filtering = false;

        return $this;
    }

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
     *
     * @throws ResourcePropertyConfigException
     */
    public function enableSorting(): self
    {
        $this->assertNullOrImplements(SortableTypeInterface::class, 'usable to sort');

        $this->sorting = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableSorting(): self
    {
        $this->sorting = false;

        return $this;
    }

    /**
     * Mark the property as initializable when creating a resource.
     *
     * Please note that {@link CreatableTypeInterface::isCreatable()} must return `true`
     * to allow the creation of resources of that type.
     *
     * By default, properties marked as initializable are required to be present in a request when
     * a resource is created. You can change that by setting the `$optional` parameter to `true`.
     *
     * @param list<TCondition> $conditions will be used to determine if a value is allowed to be set
     *
     * @return $this
     */
    public function enableInitializability(array $conditions = [], bool $optionalProperty = false): self
    {
        $this->assertNullOrImplements(CreatableTypeInterface::class, 'initializable');

        $this->initializable = new Initializability($conditions, !$optionalProperty);

        return $this;
    }

    /**
     * @return $this
     */
    public function disableInitializability(): self
    {
        $this->initializable = null;

        return $this;
    }

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
     * Simple example: if you define a property `id` for your resource but the actual entity property
     * is stored in `ident`, then you can pass `ident` as `$aliasedPath`.
     *
     * Advanced example: if you define an (alias) attribute `authorName` for your `Book`
     * resource type, you can redirect it to `$this->author->name` if `Book` has a relationship to
     * `Author` named `book` that is present in the entity backing the `Book` resource type and
     * `Author` has a property `name` that is present in the entity backing the `Author` resource
     * type.
     *
     * @param non-empty-list<non-empty-string>|PropertyPathInterface $aliasedPath all segments must have a corresponding property in the backing entity
     *
     * @return $this
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function enableAliasing(array|PropertyPathInterface $aliasedPath): self
    {
        $this->assertNullOrImplements(AliasableTypeInterface::class, 'alias');

        $this->aliasedPath = $aliasedPath instanceof PropertyPathInterface
            ? $aliasedPath->getAsNames()
            : $aliasedPath;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableAliasing(): self
    {
        $this->aliasedPath = null;

        return $this;
    }

    /**
     * @return TReadability|null
     */
    public function getReadability(): ?AbstractReadability
    {
        return $this->readability;
    }

    /**
     * @return Initializability<TCondition>|null
     */
    public function getInitializability(): ?Initializability
    {
        return $this->initializable;
    }

    /**
     * @return non-empty-list<non-empty-string>|null
     */
    public function getAliasedPath(): ?array
    {
        return $this->aliasedPath;
    }

    public function isFilteringEnabled(): bool
    {
        return $this->filtering;
    }

    public function isSortingEnabled(): bool
    {
        return $this->sorting;
    }

    /**
     * @return $this
     */
    public function disableReadability(): self
    {
        $this->readability = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableUpdatability(): self
    {
        $this->updatability = null;

        return $this;
    }

    /**
     * @return TUpdatability|null
     */
    public function getUpdatability(): ?AbstractUpdatability
    {
        return $this->updatability;
    }

    /**
     * @param class-string $implementation
     * @param non-empty-string $adjective
     *
     * @throws ResourcePropertyConfigException
     */
    protected function assertNullOrImplements(string $implementation, string $adjective): void
    {
        $type = $this->getType();

        if (!$type instanceof $implementation) {
            throw ResourcePropertyConfigException::missingImplementation($implementation, $adjective);
        }
    }

    abstract protected function getType(): TypeInterface;
}
