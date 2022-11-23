<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TRelationshipType of TransferableTypeInterface
 *
 * @template-extends AbstractUpdatability<TCondition>
 */
class AbstractRelationshipUpdatability extends AbstractUpdatability
{
    /**
     * @var TRelationshipType
     */
    private TransferableTypeInterface $relationshipType;

    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $valueConditions
     * @param TRelationshipType $relationshipType
     */
    public function __construct(
        array $entityConditions,
        array $valueConditions,
        TransferableTypeInterface $relationshipType
    ) {
        parent::__construct($entityConditions, $valueConditions);
        $this->relationshipType = $relationshipType;
    }

    /**
     * @return TRelationshipType
     */
    public function getRelationshipType(): TransferableTypeInterface
    {
        return $this->relationshipType;
    }
}
