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
class ToOneRelationshipReadability extends AbstractRelationshipReadability
{
    /**
     * @param null|callable(TEntity): (TRelationship|null) $customValueFunction
     * @param TRelationshipType $relationshipType
     */
    public function __construct(
        bool $defaultField,
        bool $allowingInconsistencies,
        bool $defaultInclude,
        private $customValueFunction,
        TransferableTypeInterface $relationshipType
    ) {
        parent::__construct($defaultField, $allowingInconsistencies, $defaultInclude, $relationshipType);
    }

    /**
     * @return null|callable(TEntity): (TRelationship|null)
     */
    public function getCustomValueFunction(): ?callable
    {
        return $this->customValueFunction;
    }
}
