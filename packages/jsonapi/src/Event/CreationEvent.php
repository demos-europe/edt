<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\RequestHandling\Body\CreationRequestBody;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AttributeSetabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipSetabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipSetabilityInterface;

/**
 * @template TEntity of object
 */
class CreationEvent
{
    protected bool $sideEffects = false;
    /**
     * @var TEntity|null
     */
    protected ?object $entity = null;

    /**
     * @param class-string<TEntity> $entityClass
     * @param list<mixed> $constructorArguments
     * @param array<non-empty-string, AttributeSetabilityInterface<PathsBasedInterface, TEntity>> $attributeSetabilities
     * @param array<non-empty-string, ToOneRelationshipSetabilityInterface<PathsBasedInterface, PathsBasedInterface, TEntity, object>> $toOneRelationshipSetabilities
     * @param array<non-empty-string, ToManyRelationshipSetabilityInterface<PathsBasedInterface, PathsBasedInterface, TEntity, object>> $toManyRelationshipSetabilities
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly array $constructorArguments,
        protected readonly array $attributeSetabilities,
        protected readonly array $toOneRelationshipSetabilities,
        protected readonly array $toManyRelationshipSetabilities,
        protected readonly CreationRequestBody $creationRequestBody
    ) {
    }

    /**
     * @return class-string<TEntity>
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return list<mixed>
     */
    public function getConstructorArguments(): array
    {
        return $this->constructorArguments;
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

    public function getCreationRequestBody(): CreationRequestBody
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
     * @param TEntity $entity
     */
    public function setEntity(object $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return TEntity|null
     */
    public function getEntity(): ?object
    {
        return $this->entity;
    }
}
