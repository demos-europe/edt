<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\JsonApi\ResourceTypes\Property;
use EDT\JsonApi\ResourceTypes\PropertyCollection;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Utilities\TypeAccessor;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * @template TCondition of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @template TSorting of \EDT\Querying\Contracts\SortMethodInterface
 */
class DynamicTransformerFactory
{
    /**
     * @var TypeAccessor<TCondition, TSorting>
     */
    private $typeAccessor;

    /**
     * @var MessageFormatter
     */
    private $messageFormatter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param TypeAccessor<TCondition, TSorting> $typeAccessor
     */
    public function __construct(
        TypeAccessor $typeAccessor,
        MessageFormatter $messageFormatter,
        LoggerInterface $logger
    ) {
        $this->typeAccessor = $typeAccessor;
        $this->messageFormatter = $messageFormatter;
        $this->logger = $logger;
    }

    /**
     * @template TEntity of object
     *
     * @param AbstractResourceType<TCondition, TSorting, TEntity> $type
     *
     * @return DynamicTransformer<TEntity>
     */
    public function createTransformer(AbstractResourceType $type, WrapperObjectFactory $wrapperFactory): DynamicTransformer
    {
        $propertyCollection = $type->getPropertyCollection();
        $attributes = $this->transformToAttributeDefinitions($type, $propertyCollection, $wrapperFactory);
        $relationships = $this->transformToIncludeDefinitions($type, $propertyCollection, $wrapperFactory);

        return new DynamicTransformer(
            $type::getName(),
            $attributes,
            $relationships,
            $this->messageFormatter,
            $this->logger
        );
    }

    /**
     * @template TEntity of object
     *
     * @param AbstractResourceType<TCondition, TSorting, TEntity> $type
     * @param PropertyCollection<TEntity>                         $propertyCollection
     *
     * @return array<non-empty-string, PropertyDefinitionInterface<TEntity, mixed>>
     */
    private function transformToAttributeDefinitions(ResourceTypeInterface $type, PropertyCollection $propertyCollection, WrapperObjectFactory $wrapperFactory): array
    {
        return array_map(static function (Property $property) use ($type, $wrapperFactory): PropertyDefinitionInterface {
            $propertyName = $property->getName();

            $propertyDefinition = new PropertyDefinition(
                $propertyName,
                $property->isDefaultField(),
                $type,
                $wrapperFactory,
                $property->getCustomReadCallback()
            );

            return $propertyDefinition;
        }, $propertyCollection->getReadableAttributes());
    }

    /**
     * @template TEntity of object
     *
     * @param AbstractResourceType<TCondition, TSorting, TEntity> $type
     * @param PropertyCollection<TEntity>                         $propertyCollection
     *
     * @return array<non-empty-string, IncludeDefinitionInterface<TEntity, object>>
     */
    private function transformToIncludeDefinitions(ResourceTypeInterface $type, PropertyCollection $propertyCollection, WrapperObjectFactory $wrapperFactory): array
    {
        $readableRelationships = $propertyCollection->getReadableRelationships();
        $internalProperties = $type->getInternalProperties();

        $includeDefinitions = [];
        foreach ($readableRelationships as $propertyName => $property) {
            $relationshipType = $this->getRelationshipTypeOrNull($propertyName, $internalProperties);
            if (null === $relationshipType) {
                continue;
            }

            $customReadCallable = $property->getCustomReadCallback();
            if (null !== $customReadCallable) {
                $customReadCallable = new TransformerObjectWrapper($customReadCallable, $relationshipType, $wrapperFactory);
            }

            $propertyDefinition = new PropertyDefinition(
                $propertyName,
                $propertyCollection->isDefaultField($propertyName),
                $type,
                $wrapperFactory,
                $customReadCallable
            );

            $includeDefinition = new IncludeDefinition($propertyDefinition, $relationshipType);
            $includeDefinitions[$property->getName()] = $includeDefinition;
        }

        return $includeDefinitions;
    }

    /**
     * @param non-empty-string                               $propertyName
     * @param array<non-empty-string, non-empty-string|null> $internalProperties
     *
     * @return ResourceTypeInterface<TCondition, TSorting, object>|null
     */
    private function getRelationshipTypeOrNull(string $propertyName, array $internalProperties): ?ResourceTypeInterface
    {
        $typeIdentifier = $internalProperties[$propertyName] ?? null;
        if (null === $typeIdentifier) {
            throw new InvalidArgumentException("Property '$propertyName' marked as relationship, but no relationship type configured in resource type.");
        }

        $type = $this->typeAccessor->requestType($typeIdentifier)
            ->availableOrNull(true)
            ->referencableOrNull(true)
            ->getTypeInstanceOrNull();

        if (!$type instanceof ResourceTypeInterface) {
            return null;
        }

        return $type;
    }
}
