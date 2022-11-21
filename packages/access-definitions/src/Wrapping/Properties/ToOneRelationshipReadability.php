<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 * @template TRelationshipType of TransferableTypeInterface<TCondition, TSorting, TRelationship>
 *
 * @template-extends AbstractRelationshipReadability<TRelationshipType>
 */
class ToOneRelationshipReadability extends AbstractRelationshipReadability
{
    /**
     * @var null|callable(TEntity): (TRelationship|null)
     */
    private $customValueFunction;

    /**
     * @param null|callable(TEntity): (TRelationship|null) $customValueFunction
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
     * @return null|callable(TEntity): (TRelationship|null)
     */
    public function getCustomValueFunction(): ?callable
    {
        return $this->customValueFunction;
    }
}
