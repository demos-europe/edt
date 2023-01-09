<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 * @template TRelationshipType of TransferableTypeInterface<TCondition, TSorting, TRelationship>
 *
 * @template-extends AbstractRelationshipReadability<TRelationshipType>
 */
class ToManyRelationshipReadability extends AbstractRelationshipReadability
{
    /**
     * @var null|callable(TEntity): iterable<TRelationship>
     */
    private $customValueFunction;

    /**
     * @param null|callable(TEntity): iterable<TRelationship> $customValueFunction
     * @param TRelationshipType $relationshipType
     */
    public function __construct(
        bool $defaultField,
        bool $allowingInconsistencies,
        bool $defaultInclude,
        ?callable $customValueFunction,
        TransferableTypeInterface $relationshipType
    ) {
        parent::__construct($defaultField, $allowingInconsistencies, $defaultInclude, $relationshipType);
        $this->customValueFunction = $customValueFunction;
    }

    /**
     * @return null|callable(TEntity): iterable<TRelationship>
     */
    public function getCustomValueFunction(): ?callable
    {
        return $this->customValueFunction;
    }
}
