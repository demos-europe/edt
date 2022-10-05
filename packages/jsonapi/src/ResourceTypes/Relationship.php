<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use League\Fractal\ParamBag;

/**
 * @template TEntity of object
 * @template TRelationship of object
 * @template-extends Property<TEntity, iterable<TRelationship>|TRelationship|null>
 */
class Relationship extends Property
{
    /**
     * @var bool
     */
    protected $defaultInclude;

    /**
     * @param non-empty-string                         $name
     * @param non-empty-list<non-empty-string>|null    $aliasedPath
     * @param null|callable(TEntity, ParamBag): (iterable<TRelationship>|TRelationship|null) $customReadCallback
     */
    public function __construct(
        string $name,
        bool $readable,
        bool $filterable,
        bool $sortable,
        ?array $aliasedPath,
        bool $defaultField,
        bool $defaultInclude,
        ?callable $customReadCallback,
        bool $allowingInconsistencies,
        bool $initializable,
        bool $requiredForCreation
    ) {
        parent::__construct(
            $name,
            $readable,
            $filterable,
            $sortable,
            $aliasedPath,
            $defaultField,
            $customReadCallback,
            $allowingInconsistencies,
            $initializable,
            $requiredForCreation
        );
        $this->defaultInclude = $defaultInclude;
    }

    public function isDefaultInclude(): bool
    {
        return $this->defaultInclude;
    }
}
