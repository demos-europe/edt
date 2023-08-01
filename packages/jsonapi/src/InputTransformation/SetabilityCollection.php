<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputTransformation;

use EDT\JsonApi\RequestHandling\Body\RequestBody;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AttributeSetabilityInterface;
use EDT\Wrapping\Properties\InitializabilityCollection;
use EDT\Wrapping\Properties\ToManyRelationshipSetabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipSetabilityInterface;
use EDT\Wrapping\Properties\UpdatablePropertyCollection;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class SetabilityCollection
{
    /**
     * @param array<non-empty-string, AttributeSetabilityInterface<TCondition, TEntity>> $attributeSetabilities
     * @param array<non-empty-string, ToOneRelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>> $toOneRelationshipSetabilities
     * @param array<non-empty-string, ToManyRelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>> $toManyRelationshipSetabilities
     */
    protected function __construct(
        protected array $attributeSetabilities,
        protected array $toOneRelationshipSetabilities,
        protected array $toManyRelationshipSetabilities
    ) {}

    /**
     * @template TCond of PathsBasedInterface
     * @template TSort of PathsBasedInterface
     * @template TEnt of object
     *
     * @param InitializabilityCollection<TEnt, TCond, TSort> $propertyCollection
     *
     * @return self<TCond, TSort, TEnt>
     */
    public static function createForCreation(
        RequestBody $requestBody,
        InitializabilityCollection $propertyCollection
    ): self {
        // remove irrelevant setability instances
        $attributeSetabilities = array_intersect_key(
            $propertyCollection->getNonConstructorAttributeSetabilities(),
            $requestBody->getAttributes()
        );
        $toOneRelationshipSetabilities = array_intersect_key(
            $propertyCollection->getNonConstructorToOneRelationshipSetabilities(),
            $requestBody->getToOneRelationships()
        );
        $toManyRelationshipSetabilities = array_intersect_key(
            $propertyCollection->getNonConstructorToManyRelationshipSetabilities(),
            $requestBody->getToManyRelationships()
        );

        return new self(
            $attributeSetabilities,
            $toOneRelationshipSetabilities,
            $toManyRelationshipSetabilities
        );
    }

    /**
     * @template TCond of PathsBasedInterface
     * @template TSort of PathsBasedInterface
     * @template TEnt of object
     *
     * @param UpdatablePropertyCollection<TCond, TSort, TEnt> $propertyCollection
     *
     * @return self<TCond, TSort, TEnt>
     */
    public static function createForUpdate(
        RequestBody $requestBody,
        UpdatablePropertyCollection $propertyCollection
    ): self {
        // remove irrelevant setability instances
        $attributeSetabilities = array_intersect_key(
            $propertyCollection->getAttributes(),
            $requestBody->getAttributes()
        );
        $toOneRelationshipSetabilities = array_intersect_key(
            $propertyCollection->getToOneRelationships(),
            $requestBody->getToOneRelationships()
        );
        $toManyRelationshipSetabilities = array_intersect_key(
            $propertyCollection->getToManyRelationships(),
            $requestBody->getToManyRelationships()
        );

        return new self(
            $attributeSetabilities,
            $toOneRelationshipSetabilities,
            $toManyRelationshipSetabilities
        );
    }


    /**
     * @return array<non-empty-string, AttributeSetabilityInterface<TCondition, TEntity>>
     */
    public function getAttributeSetabilities(): array
    {
        return $this->attributeSetabilities;
    }

    /**
     * @return array<non-empty-string, ToOneRelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>>
     */
    public function getToOneRelationshipSetabilities(): array
    {
        return $this->toOneRelationshipSetabilities;
    }

    /**
     * @return array<non-empty-string, ToManyRelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>>
     */
    public function getToManyRelationshipSetabilities(): array
    {
        return $this->toManyRelationshipSetabilities;
    }
}
