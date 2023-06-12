<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\PropertyAccessException;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\RelationshipReadabilityInterface;
use EDT\Wrapping\Utilities\TypeAccessors\ExternFilterableProcessorConfig;
use EDT\Wrapping\Utilities\TypeAccessors\ExternSortableProcessorConfig;
use function array_key_exists;

/**
 * Follows {@link PropertyPathAccessInterface} instances to check if access is
 * allowed in the context of a given root {@link TypeInterface} and maps
 * the paths according to the corresponding configured aliases.
 */
class SchemaPathProcessor
{
    /**
     * @param TypeProviderInterface<PathsBasedInterface, PathsBasedInterface> $typeProvider
     */
    public function __construct(
        private readonly PropertyPathProcessorFactory $propertyPathProcessorFactory,
        private readonly TypeProviderInterface $typeProvider
    ) {}

    /**
     * Check the paths of the given conditions for availability and applies aliases using the given type.
     *
     * @template TCondition of PathsBasedInterface
     * @template TSorting of PathsBasedInterface
     *
     * @param FilterableTypeInterface<TCondition, TSorting, object> $type
     * @param non-empty-list<TCondition> $conditions
     *
     * @throws PathException
     * @throws AccessException
     */
    public function mapFilterConditions(FilterableTypeInterface $type, array $conditions): void
    {
        $processorConfig = new ExternFilterableProcessorConfig($this->typeProvider, $type);
        $processor = $this->propertyPathProcessorFactory->createPropertyPathProcessor($processorConfig);
        array_map([$processor, 'processPropertyPaths'], $conditions);
    }

    /**
     * Check the paths of the given sort methods for availability and aliases using the given type.
     *
     * @template TCondition of PathsBasedInterface
     * @template TSorting of PathsBasedInterface
     *
     * @param SortableTypeInterface<TCondition, TSorting, object> $type
     * @param non-empty-list<TSorting>                   $sortMethods
     *
     * @throws AccessException
     * @throws PathException
     */
    public function mapSorting(SortableTypeInterface $type, array $sortMethods): void
    {
        $processorConfig = new ExternSortableProcessorConfig($this->typeProvider, $type);
        $processor = $this->propertyPathProcessorFactory->createPropertyPathProcessor($processorConfig);
        array_map([$processor, 'processPropertyPaths'], $sortMethods);
    }

    /**
     * Compares the given path with the {@link TransferableTypeInterface::getReadableProperties()}
     * of the involved types.
     *
     * @param TransferableTypeInterface<PathsBasedInterface, PathsBasedInterface, object> $type
     * @param non-empty-list<non-empty-string> $path
     *
     * @throws PropertyAccessException
     */
    public function verifyExternReadablePath(TransferableTypeInterface $type, array $path, bool $allowAttribute): void
    {
        $originalPath = $path;
        try {
            $readableProperties = $type->getReadableProperties();
            $readableProperties = $allowAttribute
                ? array_merge(...$readableProperties)
                : array_merge($readableProperties[1], $readableProperties[2]);

            $pathSegment = array_shift($path);
            if (!array_key_exists($pathSegment, $readableProperties)) {
                $availablePropertyNames = array_keys($readableProperties);
                throw PropertyAccessException::propertyNotAvailableInType($pathSegment, $type, $availablePropertyNames);
            }

            if ([] === $path) {
                return;
            }

            $property = $readableProperties[$pathSegment];
            if (!$property instanceof RelationshipReadabilityInterface) {
                throw PropertyAccessException::nonRelationship($pathSegment, $type);
            }

            $this->verifyExternReadablePath($property->getRelationshipType(), $path, $allowAttribute);
        } catch (PropertyAccessException $exception) {
            throw PropertyAccessException::pathDenied($type, $exception, $originalPath);
        }
    }
}
