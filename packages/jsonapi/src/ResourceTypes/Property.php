<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use function count;
use EDT\Querying\Contracts\PropertyPathInterface;
use InvalidArgumentException;

class Property implements SetableProperty, GetableProperty
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $readable = false;

    /**
     * @var bool
     */
    private $filterable = false;

    /**
     * @var bool
     */
    private $sortable = false;

    /**
     * @var non-empty-array<int,string>|null
     */
    private $aliasedPath;

    /**
     * @var bool
     */
    private $defaultField = false;

    /**
     * @var bool
     */
    private $defaultInclude = false;

    /**
     * @var callable|null
     */
    private $customReadCallback = null;

    /**
     * @var string|null
     */
    private $typeName = null;

    /**
     * @var bool
     */
    private $allowingInconsistencies = false;

    /**
     * @var bool
     */
    private $relationship;

    /**
     * @var bool
     */
    private $initializable = false;

    /**
     * @var bool
     */
    private $requiredForCreation = true;

    public function __construct(PropertyPathInterface $path, bool $defaultInclude, bool $relationship)
    {
        $names = $path->getAsNames();
        $namesCount = count($names);
        if (1 !== $namesCount) {
            throw new InvalidArgumentException("Expected exactly one path segment, got $namesCount");
        }

        $this->name = array_pop($names);
        $this->defaultInclude = $defaultInclude;
        $this->relationship = $relationship;
        $this->typeName = $path instanceof ResourceTypeInterface ? $path::getName() : null;
    }

    public function aliasedPath(PropertyPathInterface $aliasedPath): SetableProperty
    {
        $aliasedPath = $aliasedPath->getAsNames();
        if ([] === $aliasedPath) {
            throw new InvalidArgumentException('The path must not be empty.');
        }

        $this->aliasedPath = $aliasedPath;

        return $this;
    }

    public function filterable(): SetableProperty
    {
        $this->filterable = true;

        return $this;
    }

    public function sortable(): SetableProperty
    {
        $this->sortable = true;

        return $this;
    }

    public function readable(bool $defaultField = false, callable $customRead = null, bool $allowingInconsistencies = false): SetableProperty
    {
        $this->readable = true;
        $this->defaultField = $defaultField;
        $this->customReadCallback = $customRead;
        $this->allowingInconsistencies = $allowingInconsistencies;

        return $this;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function getAliasedPath(): ?array
    {
        return $this->aliasedPath;
    }

    public function getCustomReadCallback(): ?callable
    {
        return $this->customReadCallback;
    }

    public function isDefaultField(): bool
    {
        return $this->defaultField;
    }

    public function isDefaultInclude(): bool
    {
        return $this->defaultInclude;
    }

    public function getTypeName(): ?string
    {
        return $this->typeName;
    }

    public function isAllowingInconsistencies(): bool
    {
        return $this->allowingInconsistencies;
    }

    public function isRelationship(): bool
    {
        return $this->relationship;
    }

    public function isInitializable(): bool
    {
        return $this->initializable;
    }

    public function isRequiredForCreation(): bool
    {
        return $this->requiredForCreation;
    }

    public function initializable(bool $optional = false): SetableProperty
    {
        $this->initializable = true;
        $this->requiredForCreation = !$optional;

        return $this;
    }
}
