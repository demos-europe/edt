<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilderInterface;
use EDT\JsonApi\Utilities\PropertyBuilderFactory;
use EDT\Parsing\Utilities\Types\TypeInterface;
use EDT\PathBuilding\DocblockPropertyByTraitEvaluator;
use EDT\PathBuilding\PropertyEvaluatorPool;
use EDT\PathBuilding\PropertyTag;
use EDT\Wrapping\Contracts\ContentField;
use InvalidArgumentException;
use Webmozart\Assert\Assert;
use function array_key_exists;


/**
 * This class allows to configure a schema by exposing public properties, which property configuration instances.
 *
 * ```
 * $schemaConfig->someProperty->setFilterable();
 * ```
 *
 * It expects subclasses to define attributes and relationships as `property-read` docblock tags.
 *
 * ```
 * property-read AttributeConfigBuilderInterface<TEntity> $title
 * property-read ToOneRelationshipConfigBuilderInterface<TEntity, Publisher> $publisher
 * property-read ToManyRelationshipConfigBuilderInterface<TEntity, Person> $authors
 * ```
 *
 * When a property is accessed the magic `__get` method will be used to return the correct property config builder
 * instance for the accessed property name.
 *
 * @template TEntity of object
 *
 * @property-read IdentifierConfigBuilderInterface<TEntity> $id the property uniquely identifying instances of this type
 *
 * @template-extends AbstractResourceConfigBuilder<TEntity>
 */
abstract class MagicResourceConfigBuilder extends AbstractResourceConfigBuilder
{
    private ?DocblockPropertyByTraitEvaluator $docblockTraitEvaluator = null;

    /**
     * @var array<non-empty-string, array{TypeInterface, class-string}>
     */
    private readonly array $parsedProperties;

    /**
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        string $entityClass,
        protected readonly PropertyBuilderFactory $propertyBuilderFactory,
    ) {
        $parsedProperties = $this->getDocblockTraitEvaluator()->parseProperties(static::class, true);

        // ignore unusable types, maybe subclasses know how to handle them
        $parsedProperties = array_map(
            static fn (TypeInterface $type): array => [$type, $type->getFullyQualifiedName()],
            $parsedProperties
        );
        $parsedProperties = array_filter(
            $parsedProperties,
            static fn (array $type): bool => null !== $type[1]
        );

        // ensure only `id` is of identifier type
        Assert::keyExists($parsedProperties, ContentField::ID);
        Assert::isAOf(
            $parsedProperties[ContentField::ID][1],
            IdentifierConfigBuilderInterface::class
        );

        parent::__construct($entityClass, $this->propertyBuilderFactory->createIdentifier($entityClass));
        unset($parsedProperties[ContentField::ID]);

        $this->parsedProperties = $parsedProperties;
    }

    /**
     * @return class-string
     */
    protected function getRelationshipClass(TypeInterface $propertyType): string
    {
        // we expect the last template parameter to be the relationship class
        $templateParameter = $propertyType->getTemplateParameter(-1);
        $fqcn = $templateParameter->getFullyQualifiedName();
        Assert::notNull($fqcn);

        return $fqcn;
    }

    protected function getDocblockTraitEvaluator(): DocblockPropertyByTraitEvaluator
    {
        if (null === $this->docblockTraitEvaluator) {
            $this->docblockTraitEvaluator = PropertyEvaluatorPool::getInstance()
                ->getEvaluator([], [PropertyTag::PROPERTY_READ]);
        }

        return $this->docblockTraitEvaluator;
    }

    /**
     * Multiple accesses will return the originally initialized instance.
     *
     * @return IdentifierConfigBuilderInterface<TEntity>|AttributeConfigBuilderInterface<TEntity>|ToOneRelationshipConfigBuilderInterface<TEntity, object>|ToManyRelationshipConfigBuilderInterface<TEntity, object>
     */
    public function __get(string $name): IdentifierConfigBuilderInterface|AttributeConfigBuilderInterface|ToOneRelationshipConfigBuilderInterface|ToManyRelationshipConfigBuilderInterface
    {
        Assert::stringNotEmpty($name);

        // was the property accessed before and is thus already initialized?
        if (ContentField::ID === $name) {
            return $this->identifier;
        }
        if ($this->hasAttributeConfigBuilder($name)) {
            return $this->getAttributeConfigBuilder($name) ?? throw new InvalidArgumentException("No attribute available for property `$name`.");
        }
        if ($this->hasToOneRelationshipConfigBuilder($name)) {
            return $this->getToOneRelationshipConfigBuilder($name) ?? throw new InvalidArgumentException("No to-one relationship available for property `$name`.");
        }
        if ($this->hasToManyRelationshipConfigBuilder($name)) {
            return $this->getToManyRelationshipConfigBuilder($name) ?? throw new InvalidArgumentException("No to-many relationship available for property `$name`.");
        }

        throw new InvalidArgumentException("No usable property `$name` found.");
    }

    public function __set(string $name, mixed $value): void
    {
        throw new InvalidArgumentException("Attempt to set property `$name`, but setting properties is not implemented at all currently.");
    }

    public function __isset(string $name): bool
    {
        if ('' === $name) {
            return false;
        }

        return ContentField::ID === $name
            || $this->hasAttributeConfigBuilder($name)
            || $this->hasToOneRelationshipConfigBuilder($name)
            || $this->hasToManyRelationshipConfigBuilder($name);
    }

    public function getAttributeConfigBuilder(string $propertyName): ?AttributeConfigBuilderInterface
    {
        if (!parent::hasAttributeConfigBuilder($propertyName)) {
            $this->getParsedProperty($propertyName, AttributeConfigBuilderInterface::class);
            $builder = $this->propertyBuilderFactory->createAttribute($this->entityClass, $propertyName);
            $this->setAttributeConfigBuilder($propertyName, $builder);
        }

        return parent::getAttributeConfigBuilder($propertyName);
    }

    public function getToOneRelationshipConfigBuilder(string $propertyName): ?ToOneRelationshipConfigBuilderInterface
    {
        if (!parent::hasToOneRelationshipConfigBuilder($propertyName)) {
            $propertyType = $this->getParsedProperty($propertyName, ToOneRelationshipConfigBuilderInterface::class);
            $relationshipClass = $this->getRelationshipClass($propertyType);
            $builder = $this->propertyBuilderFactory->createToOneWithType($this->entityClass, $relationshipClass, $propertyName);
            $this->setToOneRelationshipConfigBuilder($propertyName, $builder);
        }

        return parent::getToOneRelationshipConfigBuilder($propertyName);
    }

    public function getToManyRelationshipConfigBuilder(string $propertyName): ?ToManyRelationshipConfigBuilderInterface
    {
        if (!parent::hasToManyRelationshipConfigBuilder($propertyName)) {
            $propertyType = $this->getParsedProperty($propertyName, ToManyRelationshipConfigBuilderInterface::class);
            $relationshipClass = $this->getRelationshipClass($propertyType);
            $builder = $this->propertyBuilderFactory->createToManyWithType($this->entityClass, $relationshipClass, $propertyName);
            $this->setToManyRelationshipConfigBuilder($propertyName, $builder);
        }

        return parent::getToManyRelationshipConfigBuilder($propertyName);
    }

    protected function hasAttributeConfigBuilder(string $propertyName): bool
    {
        return parent::hasAttributeConfigBuilder($propertyName)
            || $this->hasParsedProperty($propertyName, AttributeConfigBuilderInterface::class);
    }

    protected function hasToOneRelationshipConfigBuilder(string $propertyName): bool
    {
        return parent::hasToOneRelationshipConfigBuilder($propertyName)
            || $this->hasParsedProperty($propertyName, ToOneRelationshipConfigBuilderInterface::class);
    }

    protected function hasToManyRelationshipConfigBuilder(string $propertyName): bool
    {
        return parent::hasToManyRelationshipConfigBuilder($propertyName)
            || $this->hasParsedProperty($propertyName, ToManyRelationshipConfigBuilderInterface::class);
    }

    /**
     * @param non-empty-string $propertyName
     */
    protected function hasParsedProperty(string $propertyName, string $expectedPropertyBaseClass): bool
    {
        if (!array_key_exists($propertyName, $this->parsedProperties)) {
            return false;
        }

        [$parsedProperty, $propertyFqcn] = $this->parsedProperties[$propertyName];

        return $propertyFqcn === $expectedPropertyBaseClass;
    }

    /**
     * @param non-empty-string $propertyName
     * @param class-string $expectedPropertyBaseClass
     *
     * @return TypeInterface
     */
    protected function getParsedProperty(string $propertyName, string $expectedPropertyBaseClass): TypeInterface
    {
        Assert::keyExists($this->parsedProperties, $propertyName);
        [$parsedProperty, $propertyFqcn] = $this->parsedProperties[$propertyName];
        Assert::same($propertyFqcn, $expectedPropertyBaseClass);

        return $parsedProperty;
    }
}
