<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\OutputTransformation\TransformerObjectWrapper;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\Wrapping\Contracts\Types\ReadableTypeInterface;
use EDT\Wrapping\WrapperFactories\WrapperObject;
use function array_key_exists;
use function collect;
use EDT\JsonApi\OutputTransformation\DynamicTransformer;
use EDT\JsonApi\OutputTransformation\IncludeDefinition;
use EDT\JsonApi\OutputTransformation\PropertyDefinition;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\Types\CreatableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Contracts\WrapperFactoryInterface;
use EDT\Wrapping\Utilities\TypeAccessor;
use InvalidArgumentException;
use League\Fractal\TransformerAbstract;
use Psr\Log\LoggerInterface;
use function get_class;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 * @template T of object
 *
 * @template-implements ResourceTypeInterface<C, S, T>
 */
abstract class AbstractResourceType implements ResourceTypeInterface
{
    public function getReadableProperties(): array
    {
        $properties = $this->getValidatedProperties();
        $properties = array_filter($properties, [self::class, 'isReadable']);
        $properties = array_map([self::class, 'convertToNameAndTargetMapping'], $properties);

        return array_merge([], ...$properties);
    }

    public function getFilterableProperties(): array
    {
        $properties = $this->getValidatedProperties();
        $properties = array_filter($properties, [self::class, 'isFilterable']);
        $properties = array_map([self::class, 'convertToNameAndTargetMapping'], $properties);

        return array_merge([], ...$properties);
    }

    public function getSortableProperties(): array
    {
        $properties = $this->getValidatedProperties();
        $properties = array_filter($properties, [self::class, 'isSortable']);
        $properties = array_map([self::class, 'convertToNameAndTargetMapping'], $properties);

        return array_merge([], ...$properties);
    }

    /**
     * @return array<non-empty-string, null>
     *
     * @see CreatableTypeInterface::getInitializableProperties()
     */
    public function getInitializableProperties(): array
    {
        $properties = $this->getValidatedProperties();
        $properties = array_filter($properties, [self::class, 'isInitializable']);
        $properties = array_map([self::class, 'getPropertyName'], $properties);

        return array_fill_keys($properties, null);
    }

    /**
     * @return list<non-empty-string>
     */
    public function getPropertiesRequiredForCreation(): array
    {
        return collect($this->getValidatedProperties())
            ->filter(static function (GetableProperty $property): bool {
                return $property->isInitializable() && $property->isRequiredForCreation();
            })
            ->map(static function (GetableProperty $property): string {
                return $property->getName();
            })
            ->all();
    }

    public function getAliases(): array
    {
        $properties = $this->getValidatedProperties();
        $nestedAliases = array_map([$this, 'getAliasArrayItem'], $properties);
        $aliases = array_merge([], ...$nestedAliases);
        $aliases = array_filter($aliases, static function (?array $aliasedPath): bool {
            return null !== $aliasedPath;
        });

       return $aliases;
    }

    /**
     * @return array<non-empty-string, non-empty-list<non-empty-string>|null>
     */
    private function getAliasArrayItem(GetableProperty $property): array
    {
        return [$property->getName() => $property->getAliasedPath()];
    }

    public function getTransformer(): TransformerAbstract
    {
        $readableProperties = $this->getTypeAccessor()->getAccessibleReadableProperties($this);
        $defaultProperties = $this->getDefaultProperties();

        $properties = collect($this->getValidatedProperties())
            ->filter(
                static function (GetableProperty $property) use ($readableProperties): bool {
                    return array_key_exists($property->getName(), $readableProperties);
                }
            );

        // create property definitions for the attributes
        $attributes = $properties
            ->filter(
                static function (GetableProperty $property) use ($readableProperties): bool {
                    return null === $readableProperties[$property->getName()];
                }
            )
            ->mapWithKeys(
                function (GetableProperty $property) use ($defaultProperties): array {
                    return [
                        $property->getName() => new PropertyDefinition(
                            $property->getName(),
                            $defaultProperties,
                            $this,
                            $this->getWrapperFactory(),
                            $property->getCustomReadCallback()
                        ),
                    ];
                }
            )
            ->all();

        $readableRelationships = $this->extractRelationships($readableProperties);

        // create include definitions for the relationships
        $includes = $properties
            ->filter(
                static function (GetableProperty $property) use ($readableRelationships): bool {
                    return array_key_exists($property->getName(), $readableRelationships);
                }
            )
            ->mapWithKeys(
                function (GetableProperty $property) use ($readableRelationships, $defaultProperties): array {
                    $relationshipType = $readableRelationships[$property->getName()];
                    $customReadCallable = $property->getCustomReadCallback();

                    if (null !== $customReadCallable) {
                        $customReadCallable = new TransformerObjectWrapper($customReadCallable, $relationshipType, $this->getWrapperFactory());
                    }

                    $propertyDefinition = new PropertyDefinition(
                        $property->getName(),
                        $defaultProperties,
                        $this,
                        $this->getWrapperFactory(),
                        $customReadCallable
                    );

                    return [
                        $property->getName() => new IncludeDefinition(
                            $propertyDefinition,
                            $relationshipType
                        ),
                    ];
                }
            )
            ->all();

        return new DynamicTransformer(
            $this::getName(),
            $attributes,
            $includes,
            $this->getMessageFormatter(),
            $this->getLogger()
        );
    }

    /**
     * Relationships: Relationships returned by this method will only have any effect, if they are
     * {@link TypeInterface::isDirectlyAccessible() accessible} and
     * {@link TypeInterface::isReferencable() referencable}.
     *
     * Array order: Even though the order of the properties returned within the array may have an effect (e.g.
     * determining the order of properties in JSON:API responses) you can not rely on these
     * effects; they may be changed in the future.
     *
     * @return list<GetableProperty>
     */
    abstract protected function getProperties(): array;

    /**
     * @template W of object
     *
     * @return WrapperFactoryInterface<C, S, W, WrapperObject<W>>
     */
    abstract protected function getWrapperFactory(): WrapperFactoryInterface;

    /**
     * @return TypeAccessor<C, S>
     */
    abstract protected function getTypeAccessor(): TypeAccessor;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function getMessageFormatter(): MessageFormatter;

    /**
     * @param list<GetableProperty> $properties
     *
     * @return list<GetableProperty>
     */
    protected function processProperties(array $properties): array
    {
        return $properties;
    }

    protected function createAttribute(PropertyPathInterface $path): SetableProperty
    {
        return new Property($path, false, false);
    }

    protected function createToOneRelationship(
        PropertyPathInterface $path,
        bool $defaultInclude = false
    ): SetableProperty {
        return new Property($path, $defaultInclude, true);
    }

    protected function createToManyRelationship(
        PropertyPathInterface $path,
        bool $defaultInclude = false
    ): SetableProperty {
        return new Property($path, $defaultInclude, true);
    }

    /**
     * @return list<non-empty-string>
     */
    protected function getDefaultProperties(): array
    {
        return collect($this->getValidatedProperties())
            ->filter(
                static function (GetableProperty $property): bool {
                    return $property->isDefaultField();
                }
            )
            ->map(
                static function (GetableProperty $property): string {
                    return $property->getName();
                }
            )
            ->all();
    }

    /**
     * @return list<GetableProperty>
     *
     * @throws InvalidArgumentException
     *
     * @see SetableProperty::readable()
     */
    public function getValidatedProperties(): array
    {
        $properties = $this->getProperties();
        $properties = $this->processProperties($properties);
        collect($properties)->each(
            static function (GetableProperty $property): void {
                if (!$property->isAllowingInconsistencies() && null !== $property->getCustomReadCallback()) {
                    $problems = [];
                    if ($property->isFilterable()) {
                        $problems[] = 'filterable';
                    }

                    if ($property->isSortable()) {
                        $problems[] = 'sortable';
                    }

                    if (null !== $property->getAliasedPath()) {
                        $problems[] = 'being an alias';
                    }

                    if ([] !== $problems) {
                        $problems = implode(' and ', $problems);

                        throw new InvalidArgumentException("The property '{$property->getName()}' is set as {$problems} while having a custom read function set. This will likely result in inconsistencies and is not allowed by default.");
                    }
                }
            }
        );

        return $properties;
    }

    /**
     * @return array<non-empty-string, non-empty-string|null>
     */
    private static function convertToNameAndTargetMapping(GetableProperty $property): array
    {
        return [$property->getName() => $property->getTypeName()];
    }

    private static function isReadable(GetableProperty $property): bool
    {
        return $property->isReadable();
    }

    private static function isFilterable(GetableProperty $property): bool
    {
        return $property->isFilterable();
    }

    private static function isSortable(GetableProperty $property): bool
    {
        return $property->isSortable();
    }

    private static function isInitializable(GetableProperty $property): bool
    {
        return $property->isInitializable();
    }

    /**
     * @return non-empty-string
     */
    private static function getPropertyName(GetableProperty $property): string
    {
        return $property->getName();
    }

    /**
     * @param array<non-empty-string, ReadableTypeInterface<C, S, object>|null> $properties
     *
     * @return array<non-empty-string, ResourceTypeInterface<C, S, object>>
     */
    private function extractRelationships(array $properties): array
    {
        $readableRelationships = array_filter(
            $properties,
            static function (?ReadableTypeInterface $property): bool {
                return null !== $property;
            }
        );
        return array_map(static function (ReadableTypeInterface $property): ResourceTypeInterface {
            if (!$property instanceof ResourceTypeInterface) {
                $typeClass = get_class($property);
                throw new InvalidArgumentException("Relationship does not reference a resource type but '$typeClass' instead.");
            }

            return $property;
        }, $readableRelationships);
    }
}
