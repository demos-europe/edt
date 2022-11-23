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
 * @template-extends AbstractRelationshipUpdatability<TCondition, TRelationshipType>
 */
class ToOneRelationshipUpdatability extends AbstractRelationshipUpdatability
{
    /**
     * @var null|callable(TEntity, TRelationship|null): void
     */
    private $customWriteFunction;

    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $valueConditions
     * @param TRelationshipType $relationshipType
     * @param null|callable(TEntity, TRelationship|null): void $customWriteFunction
     */
    public function __construct(
        array $entityConditions,
        array $valueConditions,
        TransferableTypeInterface $relationshipType,
        ?callable $customWriteFunction
    ) {
        parent::__construct($entityConditions, $valueConditions, $relationshipType);
        $this->customWriteFunction = $customWriteFunction;
    }

    /**
     * @return null|callable(TEntity, TRelationship|null): void
     */
    public function getCustomWriteFunction(): ?callable
    {
        return $this->customWriteFunction;
    }
}
