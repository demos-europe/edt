<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

/**
 * @template TRelationshipType of TransferableTypeInterface
 */
abstract class AbstractRelationshipReadability extends AbstractReadability
{
    protected bool $defaultInclude;

    /**
     * @var TRelationshipType
     */
    private TransferableTypeInterface $relationshipType;

    /**
     * @param TRelationshipType $relationshipType
     */
    public function __construct(
        bool $defaultField,
        bool $allowingInconsistencies,
        bool $defaultInclude,
        TransferableTypeInterface $relationshipType
    ) {
        parent::__construct($defaultField, $allowingInconsistencies);
        $this->defaultInclude = $defaultInclude;
        $this->relationshipType = $relationshipType;
    }

    public function isDefaultInclude(): bool
    {
        return $this->defaultInclude;
    }

    /**
     * @return TRelationshipType
     */
    public function getRelationshipType(): TransferableTypeInterface
    {
        return $this->relationshipType;
    }
}
