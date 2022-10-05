<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathInterface;

/**
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends PropertyBuilder<TEntity, TRelationship|iterable<TRelationship>|null>
 */
class RelationshipBuilder extends PropertyBuilder
{
    /**
     * @var bool
     */
    private $defaultInclude;

    /**
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship> $path
     * @param class-string<TEntity>                                     $entityClass
     *
     * @throws PathException
     */
    public function __construct(PropertyPathInterface $path, string $entityClass, bool $defaultInclude)
    {
        parent::__construct($path, $entityClass);
        $this->defaultInclude = $defaultInclude;
    }

    public function build(): Property
    {
        return new Relationship(
            $this->name,
            $this->readable,
            $this->filterable,
            $this->sortable,
            $this->aliasedPath,
            $this->defaultField,
            $this->defaultInclude,
            $this->customReadCallback,
            $this->allowingInconsistencies,
            $this->initializable,
            $this->requiredForCreation
        );
    }
}
