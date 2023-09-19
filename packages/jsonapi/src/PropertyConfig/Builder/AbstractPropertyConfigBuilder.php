<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathInterface;

/**
 * Supports common tasks to set up a specific property for accesses via the generic JSON:API implementation.
 *
 * * {@link AbstractPropertyConfigBuilder::filterable filtering via property values}
 * * {@link AbstractPropertyConfigBuilder::sortable sorting via property values}
 *
 * You can also mark the property as an alias by setting {@link AbstractPropertyConfigBuilder::aliasedPath()}.
 * This will result in all accesses that aren't using custom logic using the provided path
 * and thus expecting that for the given path segments corresponding properties in the backend
 * entity exist.
 */
abstract class AbstractPropertyConfigBuilder implements PropertyConfigBuilderInterface
{
    protected bool $filterable = false;

    protected bool $sortable = false;

    /**
     * @var non-empty-list<non-empty-string>|null
     */
    protected ?array $aliasedPath = null;

    /**
     * @param non-empty-string $name
     *
     * @throws PathException
     */
    public function __construct(
        protected readonly string $name
    ) {}

    /**
     * @param non-empty-list<non-empty-string>|PropertyPathInterface $aliasedPath
     *
     * @return $this
     *
     * @throws PathException
     */
    public function aliasedPath(array|PropertyPathInterface $aliasedPath): self
    {
        $this->aliasedPath = $aliasedPath instanceof PropertyPathInterface
            ? $aliasedPath->getAsNames()
            : $aliasedPath;

        return $this;
    }

    /**
     * @return $this
     */
    public function filterable(): self
    {
        $this->filterable = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function sortable(): self
    {
        $this->sortable = true;

        return $this;
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    protected function getPropertyPath(): array
    {
        return $this->aliasedPath ?? [$this->name];
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
