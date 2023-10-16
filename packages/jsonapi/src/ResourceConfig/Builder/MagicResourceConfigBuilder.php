<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilderInterface;
use EDT\JsonApi\Utilities\PropertyBuilderFactory;
use EDT\Parsing\Utilities\ClassOrInterfaceType;
use EDT\Parsing\Utilities\TypeInterface;
use EDT\PathBuilding\DocblockPropertyByTraitEvaluator;
use EDT\PathBuilding\PropertyEvaluatorPool;
use EDT\PathBuilding\PropertyTag;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\ContentField;
use InvalidArgumentException;
use Webmozart\Assert\Assert;
use function array_key_exists;


// FIXME: unsure that if a property-read is extended with a different type that this type is used, not the one in the parent class,
// this is needed to support the use case that an entity has a to-one relationship to an email address, but the frontend should receive
// the email address as string attribute with the same attribute name as the relationship in the entity

/**
 * Expects subclasses to define attributes and relationships as `property-read` docblock tags.
 *
 * ```
 * property-read AttributeConfigBuilderInterface<TCondition, TEntity> $title
 * property-read ToOneRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, Publisher> $publisher
 * property-read ToManyRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, Person> $authors
 * ```
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @property-read IdentifierConfigBuilderInterface<TEntity> $id
 *
 * @template-extends AbstractResourceConfigBuilder<TCondition, TSorting, TEntity>
 */
abstract class MagicResourceConfigBuilder extends AbstractResourceConfigBuilder
{
    private ?DocblockPropertyByTraitEvaluator $docblockTraitEvaluator = null;

    /**
     * @param class-string<TEntity> $entityClass
     * @param PropertyBuilderFactory<TCondition, TSorting> $propertyBuilderFactory
     */
    public function __construct(
        string $entityClass,
        protected readonly PropertyBuilderFactory $propertyBuilderFactory,
    ) {
        $parsedProperties = $this->getDocblockTraitEvaluator()->parseProperties(static::class, true);
        Assert::keyExists($parsedProperties, ContentField::ID);

        // ensure only `id` is of identifier type
        Assert::isInstanceOf($parsedProperties[ContentField::ID], IdentifierConfigBuilderInterface::class);
        parent::__construct($entityClass, $this->propertyBuilderFactory->createIdentifier($entityClass));
        unset($parsedProperties[ContentField::ID]);

        // ignore unusable types, maybe subclasses know how to handle them
        $parsedProperties = array_filter(
            $parsedProperties,
            static fn (TypeInterface $type): bool => $type instanceof ClassOrInterfaceType
        );

        foreach ($parsedProperties as $propertyName => $propertyType) {
            $propertyBaseClass = $propertyType->getFullyQualifiedName();
            switch ($propertyBaseClass) {
                case AttributeConfigBuilderInterface::class:
                    $this->attributes[$propertyName] = $this->propertyBuilderFactory
                        ->createAttribute($this->entityClass, $propertyName);
                    break;
                case ToOneRelationshipConfigBuilderInterface::class:
                    $relationshipClass = $this->getRelationshipClass($propertyType);
                    $this->toOneRelationships[$propertyName] = $this->propertyBuilderFactory
                        ->createToOneWithType($this->entityClass, $relationshipClass, $propertyName);
                    break;
                case ToManyRelationshipConfigBuilderInterface::class:
                    $relationshipClass = $this->getRelationshipClass($propertyType);
                    $this->toManyRelationships[$propertyName] = $this->propertyBuilderFactory
                        ->createToManyWithType($this->entityClass, $relationshipClass, $propertyName);
                    break;
                default:
                    // ignore unusable types, maybe subclasses know how to handle them
                    break;
            }
        }
    }

    /**
     * @return class-string
     */
    protected function getRelationshipClass(ClassOrInterfaceType $propertyType): string
    {
        // we expect the last template parameter to be the relationship class
        $templateParameter = $propertyType->getTemplateParameter(-1);
        Assert::isInstanceOf($templateParameter, ClassOrInterfaceType::class);

        return $templateParameter->getFullyQualifiedName();
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
     * @return IdentifierConfigBuilderInterface<TEntity>|AttributeConfigBuilderInterface<TCondition, TEntity>|ToOneRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, object>|ToManyRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, object>
     */
    public function __get(string $name): IdentifierConfigBuilderInterface|AttributeConfigBuilderInterface|ToOneRelationshipConfigBuilderInterface|ToManyRelationshipConfigBuilderInterface
    {
        // was the property accessed before and is thus already initialized?
        if (ContentField::ID === $name) {
            return $this->identifier;
        }
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        if (array_key_exists($name, $this->toOneRelationships)) {
            return $this->toOneRelationships[$name];
        }
        if (array_key_exists($name, $this->toManyRelationships)) {
            return $this->toManyRelationships[$name];
        }

        throw new InvalidArgumentException("No usable property `$name` found.");
    }

    public function __set(string $name, mixed $value): void
    {
        throw new InvalidArgumentException("Attempt to set property `$name`, but setting properties is not implemented at all currently.");
    }

    public function __isset(string $name): bool
    {
        return ContentField::ID === $name
            || array_key_exists($name, $this->attributes)
            || array_key_exists($name, $this->toOneRelationships)
            || array_key_exists($name, $this->toManyRelationships);
    }
}
