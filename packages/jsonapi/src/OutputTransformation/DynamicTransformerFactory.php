<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\JsonApi\ResourceTypes\Property;
use EDT\JsonApi\ResourceTypes\PropertyCollection;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
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
        $readableProperties = $this->getReadableResourceTypeProperties($type);

        $includeDefinitions = [];
        foreach ($readableRelationships as $propertyName => $property) {
            $relationshipType = $readableProperties[$propertyName] ?? null;
            if (null === $relationshipType) {
                // do not create IncludeDefinitions for non-resource/non-readable relationships
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
     * @param ResourceTypeInterface<TCondition, TSorting, object> $type
     *
     * @return array<non-empty-string, ResourceTypeInterface<TCondition, TSorting, object>>
     */
    private function getReadableResourceTypeProperties(ResourceTypeInterface $type): array
    {
        $readableProperties = array_map(
            static function (?TransferableTypeInterface $relationshipType): ?ResourceTypeInterface {
                if (null === $relationshipType) {
                    return null;
                }
                if (!$relationshipType instanceof ResourceTypeInterface) {
                    return null;
                }
                return $relationshipType;
            },
            $type->getReadableProperties()
        );

        return array_filter(
            $readableProperties,
            static fn (?ResourceTypeInterface $relationshipType): bool => null !== $relationshipType
        );
    }
}
