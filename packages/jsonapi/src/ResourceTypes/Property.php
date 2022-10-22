<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use League\Fractal\ParamBag;

/**
 * @template TEntity of object
 * @template TValue
 */
class Property
{
    /**
     * @var non-empty-string
     */
    private string $name;

    private bool $readable;

    private bool $filterable;

    private bool $sortable;

    /**
     * @var non-empty-list<non-empty-string>|null
     */
    private ?array $aliasedPath;

    private bool $defaultField;

    /**
     * @var null|callable(TEntity, ParamBag): TValue
     */
    private $customReadCallback;

    private bool $allowingInconsistencies;

    private bool $initializable;

    private bool $requiredForCreation;

    /**
     * @param non-empty-string                         $name
     * @param non-empty-list<non-empty-string>|null    $aliasedPath
     * @param null|callable(TEntity, ParamBag): TValue $customReadCallback
     */
    public function __construct(
        string $name,
        bool $readable,
        bool $filterable,
        bool $sortable,
        ?array $aliasedPath,
        bool $defaultField,
        ?callable $customReadCallback,
        bool $allowingInconsistencies,
        bool $initializable,
        bool $requiredForCreation
    ) {
        $this->name = $name;
        $this->readable = $readable;
        $this->filterable = $filterable;
        $this->sortable = $sortable;
        $this->aliasedPath = $aliasedPath;
        $this->defaultField = $defaultField;
        $this->customReadCallback = $customReadCallback;
        $this->allowingInconsistencies = $allowingInconsistencies;
        $this->initializable = $initializable;
        $this->requiredForCreation = $requiredForCreation;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }
    /**
     * @return non-empty-string
     */
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

    /**
     * @return non-empty-list<non-empty-string>|null
     */
    public function getAliasedPath(): ?array
    {
        return $this->aliasedPath;
    }

    public function isDefaultField(): bool
    {
        return $this->defaultField;
    }

    /**
     * @return null|callable(TEntity, ParamBag): TValue
     */
    public function getCustomReadCallback(): ?callable
    {
        return $this->customReadCallback;
    }

    public function isAllowingInconsistencies(): bool
    {
        return $this->allowingInconsistencies;
    }

    public function isInitializable(): bool
    {
        return $this->initializable;
    }

    public function isRequiredForCreation(): bool
    {
        return $this->requiredForCreation;
    }
}
