<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\RequestHandling\Body\UpdateRequestBody;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AttributeSetabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipSetabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipSetabilityInterface;

/**
 * @template TEntity of object
 */
class UpdateEvent
{
    protected bool $sideEffects = false;

    /**
     * @param TEntity $entity
     * @param array<non-empty-string, AttributeSetabilityInterface<PathsBasedInterface, TEntity>> $attributeSetabilities
     * @param array<non-empty-string, ToOneRelationshipSetabilityInterface<PathsBasedInterface, PathsBasedInterface, TEntity, object>> $toOneRelationshipSetabilities
     * @param array<non-empty-string, ToManyRelationshipSetabilityInterface<PathsBasedInterface, PathsBasedInterface, TEntity, object>> $toManyRelationshipSetabilities
     */
    public function __construct(
        protected readonly object $entity,
        protected readonly array $attributeSetabilities,
        protected readonly array $toOneRelationshipSetabilities,
        protected readonly array $toManyRelationshipSetabilities,
        protected readonly UpdateRequestBody $creationRequestBody
    ) {
    }

    /**
     * @return array<non-empty-string, AttributeSetabilityInterface<PathsBasedInterface, TEntity>>
     */
    public function getAttributeSetabilities(): array
    {
        return $this->attributeSetabilities;
    }

    /**
     * @return array<non-empty-string, ToOneRelationshipSetabilityInterface<PathsBasedInterface, PathsBasedInterface, TEntity, object>>
     */
    public function getToOneRelationshipSetabilities(): array
    {
        return $this->toOneRelationshipSetabilities;
    }

    /**
     * @return array<non-empty-string, ToManyRelationshipSetabilityInterface<PathsBasedInterface, PathsBasedInterface, TEntity, object>>
     */
    public function getToManyRelationshipSetabilities(): array
    {
        return $this->toManyRelationshipSetabilities;
    }

    public function getUpdateRequestBody(): UpdateRequestBody
    {
        return $this->creationRequestBody;
    }

    public function setSideEffects(bool $sideEffects): void
    {
        $this->sideEffects = $sideEffects;
    }

    public function hasSideEffects(): bool
    {
        return $this->sideEffects;
    }

    /**
     * @return TEntity
     */
    public function getEntity(): object
    {
        return $this->entity;
    }
}
