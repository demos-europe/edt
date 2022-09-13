<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use Closure;
use EDT\JsonApi\OutputTransformation\TransformerObjectWrapper;
use EDT\Wrapping\WrapperFactories\WrapperObject;
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

/**
 * @template T of object
 *
 * @template-implements ResourceTypeInterface<T>
 */
abstract class AbstractResourceType implements ResourceTypeInterface
{
    public function getReadableProperties(): array
    {
        return collect($this->getValidatedProperties())
            ->filter(
                static function (GetableProperty $property): bool {
                    return $property->isReadable();
                }
            )
            ->mapWithKeys(Closure::fromCallable([self::class, 'convertToNameAndTargetMapping']))
            ->all();
    }

    public function getFilterableProperties(): array
    {
        return collect($this->getValidatedProperties())
            ->filter(
                static function (GetableProperty $property): bool {
                    return $property->isFilterable();
                }
            )
            ->mapWithKeys(Closure::fromCallable([self::class, 'convertToNameAndTargetMapping']))
            ->all();
    }

    public function getSortableProperties(): array
    {
        return collect($this->getValidatedProperties())
            ->filter(
                static function (GetableProperty $property): bool {
                    return $property->isSortable();
                }
            )
            ->mapWithKeys(Closure::fromCallable([self::class, 'convertToNameAndTargetMapping']))
            ->all();
    }

    /**
     * @return array<string,null>
     *
     * @see CreatableTypeInterface::getInitializableProperties()
     */
    public function getInitializableProperties(): array
    {
        return collect($this->getValidatedProperties())
            ->filter(static function (GetableProperty $property): bool {
                return $property->isInitializable();
            })
            ->mapWithKeys(static function (GetableProperty $property): array {
                return [$property->getName() => null];
            })
            ->all();
    }

    /**
     * @return array<int, string>
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
        return collect($this->getValidatedProperties())
            ->mapWithKeys(
                static function (GetableProperty $property): array {
                    return [$property->getName() => $property->getAliasedPath()];
                }
            )
            ->filter(
                static function (?array $aliasedPath): bool {
                    return null !== $aliasedPath;
                }
            )
            ->all();
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

        // create include definitions for the relationships
        $includes = $properties
            ->filter(
                static function (GetableProperty $property) use ($readableProperties): bool {
                    return null !== $readableProperties[$property->getName()];
                }
            )
            ->mapWithKeys(
                function (GetableProperty $property) use (
                    $readableProperties,
                    $defaultProperties
                ): array {
                    $relationshipType = $readableProperties[$property->getName()];
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

        return new DynamicTransformer($this::getName(), $attributes, $includes, $this->getLogger());
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
     * @return array<int, GetableProperty>
     */
    abstract protected function getProperties(): array;

    /**
     * @template W of object
     *
     * @return WrapperFactoryInterface<W, WrapperObject<W>>
     */
    abstract protected function getWrapperFactory(): WrapperFactoryInterface;

    abstract protected function getTypeAccessor(): TypeAccessor;

    abstract protected function getLogger(): LoggerInterface;

    /**
     * @param array<int, GetableProperty> $properties
     *
     * @return array<int, GetableProperty>
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
     * @return array<int,string>
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
     * @return array<int,GetableProperty>
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
     * @return array<string, string|null>
     */
    private static function convertToNameAndTargetMapping(GetableProperty $property): array
    {
        return [$property->getName() => $property->getTypeName()];
    }
}
