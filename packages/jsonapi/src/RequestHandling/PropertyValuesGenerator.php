<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use EDT\JsonApi\Schema\RelationshipObject;
use EDT\JsonApi\Schema\ResourceIdentifierObject;
use EDT\JsonApi\Schema\ToManyResourceLinkage;
use EDT\JsonApi\Schema\ToOneResourceLinkage;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use InvalidArgumentException;

/**
 * @psalm-type JsonApiRelationship = array{type: string, id: string}
 * @psalm-type JsonApiRelationships = array<string,array{data: array<int, JsonApiRelationship>|JsonApiRelationship|null}>
 */
class PropertyValuesGenerator
{
    /**
     * @var TypeProviderInterface
     */
    private $typeProvider;

    /**
     * @var EntityFetcherInterface
     */
    private $entityFetcher;

    public function __construct(EntityFetcherInterface $entityFetcher, TypeProviderInterface $typeProvider)
    {
        $this->typeProvider = $typeProvider;
        $this->entityFetcher = $entityFetcher;
    }

    /**
     * Converts the attributes and relationships from the JSON:API request format into
     * a single list, mapping the property names to the actual values to set.
     *
     * @param array<string, mixed|null> $attributes
     * @param JsonApiRelationships      $relationships
     *
     * @return array<string,mixed>
     */
    public function generatePropertyValues(array $attributes, array $relationships): array
    {
        $relationships = array_map(
            [RelationshipObject::class, 'createWithDataRequired'],
            $relationships
        );

        $relationships = array_map(function (RelationshipObject $relationshipObject) {
            $resourceLinkage = $relationshipObject->getData();

            if ($resourceLinkage->getCardinality()->isToMany() && $resourceLinkage instanceof ToManyResourceLinkage) {
                return new ArrayCollection($this->getRelationshipEntities($resourceLinkage));
            }

            if ($resourceLinkage->getCardinality()->isToOne() && $resourceLinkage instanceof ToOneResourceLinkage) {
                return $this->getEntityForResourceLinkage($resourceLinkage);
            }

            throw new InvalidArgumentException('Resource linkage not supported');
        }, $relationships);

        return $this->preventDuplicatedFieldNames($attributes, $relationships);
    }

    /**
     * @param array<string,mixed> $attributes
     * @param array<string,mixed> $relationships
     *
     * @return array<string,mixed>
     *
     * @throws InvalidArgumentException
     */
    private function preventDuplicatedFieldNames(array $attributes, array $relationships): array
    {
        $fieldKeys = array_unique(array_merge(array_keys($attributes), array_keys($relationships)));
        if (count($fieldKeys) !== count($attributes) + count($relationships)) {
            throw new InvalidArgumentException('Attribute and relationship keys must be distinct');
        }

        return array_merge($attributes, $relationships);
    }

    private function getEntityForResourceLinkage(ToOneResourceLinkage $resourceLinkage): ?object
    {
        $resourceIdentifierObject = $resourceLinkage->getResourceIdentifierObject();
        if (null === $resourceIdentifierObject) {
            return null;
        }

        return $this->getRelationshipEntity($resourceIdentifierObject);
    }

    /**
     * @return array<int,object>
     */
    private function getRelationshipEntities(ToManyResourceLinkage $resourceLinkage): array
    {
        return array_map(
            [$this, 'getRelationshipEntity'],
            $resourceLinkage->getResourceIdentifierObjects()
        );
    }

    private function getRelationshipEntity(ResourceIdentifierObject $resourceIdentifierObject): object
    {
        $typeName = $resourceIdentifierObject->getType();

        $type = $this->typeProvider->getAvailableType($typeName, ResourceTypeInterface::class);
        $id = $resourceIdentifierObject->getId();

        return $this->entityFetcher->getEntityByTypeIdentifier($type, $id);
    }
}
