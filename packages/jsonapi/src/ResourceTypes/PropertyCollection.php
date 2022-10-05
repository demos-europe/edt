<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use InvalidArgumentException;
use function array_key_exists;
use function count;

/**
 * @template TEntity of object
 */
class PropertyCollection
{
    /**
     * @var array<non-empty-string, Property<TEntity, mixed>>
     */
    private $properties;

    /**
     * @var array<non-empty-string, Property<TEntity, mixed>>
     */
    private $defaultProperties;

    /**
     * @param list<Property<TEntity, mixed>> $properties
     */
    public function __construct(array $properties)
    {
        array_map([$this, 'validateConsistency'], $properties);
        $propertyPairs = array_map([$this, 'asKeyedPair'], $properties);
        $this->properties = array_merge([], ...$propertyPairs);
        if (count($propertyPairs) !== count($this->properties)) {
            throw new InvalidArgumentException('Given properties must not contain duplicated names.');
        }
        $this->defaultProperties = array_filter(
            $this->properties,
            static function (Property $property): bool {
                return $property->isDefaultField();
            }
        );
    }

    /**
     * @param non-empty-string $propertyName
     */
    public function has(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->properties);
    }

    /**
     * @param non-empty-string $propertyName
     */
    public function isDefaultField(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->defaultProperties);
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @return Property<TEntity, mixed>
     */
    public function get(string $propertyName): Property
    {
        return $this->properties[$propertyName];
    }

    /**
     * @return array<non-empty-string, Property<TEntity, mixed>>
     */
    public function getFilterableProperties(): array
    {
        return array_filter($this->properties, [$this, 'isFilterable']);
    }

    /**
     * @return array<non-empty-string, Property<TEntity, mixed>>
     */
    public function getReadableProperties(): array
    {
        return array_filter($this->properties, [$this, 'isReadable']);
    }

    /**
     * @return array<non-empty-string, Property<TEntity, mixed>>
     */
    public function getReadableAttributes(): array
    {
        $readableProperties = array_filter($this->properties, [$this, 'isReadable']);

        return array_filter($readableProperties, static function (Property $property): bool {
            return !$property instanceof Relationship;
        });
    }

    /**
     * @return array<non-empty-string, Relationship<TEntity, object>>
     */
    public function getReadableRelationships(): array
    {
        $readableProperties = array_filter($this->properties, [$this, 'isReadable']);

        return array_filter($readableProperties, static function (Property $property): bool {
            return $property instanceof Relationship;
        });
    }

    /**
     * @return array<non-empty-string, Property<TEntity, mixed>>
     */
    public function getSortableProperties(): array
    {
        return array_filter($this->properties, [$this, 'isSortable']);
    }

    /**
     * @return array<non-empty-string, Property<TEntity, mixed>>
     */
    public function getInitializableProperties(): array
    {
        return array_filter($this->properties, [$this, 'isInitializable']);
    }

    /**
     * @return list<non-empty-string>
     */
    public function getPropertyNamesRequiredForCreation(): array
    {
        $properties = array_filter($this->properties, static function (Property $property): bool {
            return $property->isInitializable() && $property->isRequiredForCreation();
        });

        return array_keys($properties);
    }

    /**
     * @return array<non-empty-string, non-empty-list<non-empty-string>>
     */
    public function getAliasPaths(): array
    {
        $aliases = array_map(static function (Property $property): ?array {
            return $property->getAliasedPath();
        }, $this->properties);

        return array_filter($aliases, static function (?array $aliasedPath): bool {
            return null !== $aliasedPath;
        });
    }

    /**
     * @param Property<TEntity, mixed> $property
     *
     * @return non-empty-array<non-empty-string, Property<TEntity, mixed>>
     */
    private function asKeyedPair(Property $property): array
    {
        return [$property->getName() => $property];
    }

    /**
     * @param Property<TEntity, mixed> $property
     *
     * @throws InvalidArgumentException
     */
    private function validateConsistency(Property $property): void
    {
        if ($property->isAllowingInconsistencies() || null === $property->getCustomReadCallback()) {
            return;
        }

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

            throw new InvalidArgumentException("The property '{$property->getName()}' is set as $problems while having a custom read function set. This will likely result in inconsistencies and is not allowed by default.");
        }
    }

    /**
     * @param Property<TEntity, mixed> $property
     */
    private function isReadable(Property $property): bool
    {
        return $property->isReadable();
    }

    /**
     * @param Property<TEntity, mixed> $property
     */
    private function isFilterable(Property $property): bool
    {
        return $property->isFilterable();
    }

    /**
     * @param Property<TEntity, mixed> $property
     */
    private function isSortable(Property $property): bool
    {
        return $property->isSortable();
    }

    /**
     * @param Property<TEntity, mixed> $property
     */
    private function isInitializable(Property $property): bool
    {
        return $property->isInitializable();
    }
}
