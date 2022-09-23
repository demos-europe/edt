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
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 *
 * @psalm-type JsonApiRelationship = array{type: non-empty-string, id: non-empty-string}
 * @psalm-type JsonApiRelationships = array<non-empty-string,array{data: list<JsonApiRelationship>|JsonApiRelationship|null}>
 */
class PropertyValuesGenerator
{
    /**
     * @var TypeProviderInterface<C, S>
     */
    private $typeProvider;

    /**
     * @var EntityFetcherInterface<C, S>
     */
    private $entityFetcher;

    /**
     * @param EntityFetcherInterface<C, S> $entityFetcher
     * @param TypeProviderInterface<C, S>  $typeProvider
     */
    public function __construct(EntityFetcherInterface $entityFetcher, TypeProviderInterface $typeProvider)
    {
        $this->typeProvider = $typeProvider;
        $this->entityFetcher = $entityFetcher;
    }

    /**
     * Converts the attributes and relationships from the JSON:API request format into
     * a single list, mapping the property names to the actual values to set.
     *
     * @param array<non-empty-string, mixed|null> $attributes
     * @param JsonApiRelationships                $relationships
     *
     * @return array<non-empty-string, mixed>
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
     * @param array<non-empty-string, mixed> $attributes
     * @param array<non-empty-string, mixed> $relationships
     *
     * @return array<non-empty-string, mixed>
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
     * @return list<object>
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

        $type = $this->typeProvider->requestType($typeName)
            ->instanceOf(ResourceTypeInterface::class)
            ->available(true)
            ->getTypeInstance();
        $id = $resourceIdentifierObject->getId();

        return $this->entityFetcher->getEntityByTypeIdentifier($type, $id);
    }
}
