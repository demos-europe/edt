<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\JsonApi\ResourceTypes\Property;
use EDT\JsonApi\ResourceTypes\PropertyCollection;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * @template TCondition of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @template TSorting of \EDT\Querying\Contracts\SortMethodInterface
 */
class DynamicTransformerFactory
{
    private MessageFormatter $messageFormatter;

    private LoggerInterface $logger;

    public function __construct(MessageFormatter $messageFormatter, LoggerInterface $logger)
    {
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
        return array_map(static fn (Property $property): PropertyDefinitionInterface => new PropertyDefinition(
            $property->getName(),
            $property->isDefaultField(),
            $type,
            $wrapperFactory,
            $property->getCustomReadCallback()
        ), $propertyCollection->getReadableAttributes());
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
        $readableProperties = $type->getReadableProperties();

        $includeDefinitions = [];
        foreach ($readableRelationships as $propertyName => $property) {
            $relationshipType = $this->getRelationshipTypeOrNull($propertyName, $readableProperties);
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
     * @param non-empty-string                                                                      $propertyName
     * @param array<non-empty-string, TransferableTypeInterface<TCondition, TSorting, object>|null> $readableProperties
     *
     * @return ResourceTypeInterface<TCondition, TSorting, object>|null
     */
    private function getRelationshipTypeOrNull(string $propertyName, array $readableProperties): ?ResourceTypeInterface
    {
        $relationshipType = $readableProperties[$propertyName] ?? null;
        if (null === $relationshipType) {
            throw new InvalidArgumentException("Property '$propertyName' marked as relationship, but no relationship type was configured as readable in resource type.");
        }

        return $relationshipType instanceof ResourceTypeInterface
            ? $relationshipType
            : null;
    }
}
