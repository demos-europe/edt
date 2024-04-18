<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ApiDocumentation\DefaultField;
use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\JsonApi\PropertyConfig\AttributeConfigInterface;
use EDT\JsonApi\PropertyConfig\DtoAttributeConfig;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\PropertyPaths\NonRelationshipLink;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeConstructorBehavior;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\CallbackAttributeReadability;
use EDT\Wrapping\PropertyBehavior\Attribute\Factory\CallbackAttributeSetBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Attribute\Factory\PathAttributeSetBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Attribute\PathAttributeReadability;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityFactoryInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityInterface;
use EDT\Wrapping\Utilities\AttributeTypeResolverInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements AttributeConfigBuilderInterface<TCondition, TEntity>
 * @template-implements BuildableInterface<AttributeConfigInterface<TCondition, TEntity>>
 * @template-extends AbstractPropertyConfigBuilder<TEntity, TCondition, simple_primitive|array<int|string, mixed>|null, ConstructorBehaviorFactoryInterface, PropertyUpdatabilityFactoryInterface<TCondition, TEntity>, PropertyUpdatabilityFactoryInterface<TCondition, TEntity>>
 */
class AttributeConfigBuilder
    extends AbstractPropertyConfigBuilder
    implements AttributeConfigBuilderInterface, BuildableInterface
{
    /**
     * TODO: replace with interface and refactor to template-based property in parent?
     *
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>): AttributeReadabilityInterface<TEntity>
     */
    protected $readabilityFactory;

    /**
     * @param non-empty-string $name
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        string $name,
        protected readonly string $entityClass,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolverInterface $typeResolver
    ) {
        parent::__construct($name);
    }

    public function initializable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null
    ): self {
        if ($constructorArgument) {
            $this->addConstructorBehavior(
                AttributeConstructorBehavior::createFactory($customConstructorArgumentName, OptionalField::NO, null)
            );
        }

        $optional = OptionalField::fromBoolean($optionalAfterConstructor);

        return null === $postConstructorCallback
            ? $this->addPathCreationBehavior($optional)
            : $this->addCreationBehavior(
                new CallbackAttributeSetBehaviorFactory([], $postConstructorCallback, $optional)
            );
    }

    public function addPathCreationBehavior(OptionalField $optional = OptionalField::NO, array $entityConditions = []): self
    {
        return $this->addCreationBehavior(
            new PathAttributeSetBehaviorFactory($this->propertyAccessor, $entityConditions, $optional)
        );
    }

    public function addPathUpdateBehavior(array $entityConditions = []): self
    {
        return $this->addUpdateBehavior(
            new PathAttributeSetBehaviorFactory($this->propertyAccessor, $entityConditions, OptionalField::YES)
        );
    }

    public function setReadableByPath(DefaultField $defaultField = DefaultField::NO): self
    {
        return $this->readable($defaultField->equals(DefaultField::YES));
    }

    public function setReadableByCallable(callable $behavior, DefaultField $defaultField = DefaultField::NO): self
    {
        return $this->readable($defaultField->equals(DefaultField::YES), $behavior);
    }

    public function readable(bool $defaultField = false, callable $customReadCallback = null): self
    {
        // the usage of an invokable class instead of an anonymous function is complicated, but the only way to properly
        // pass around phpstan types without using actual classes (which MAY be the better solution)
        $this->readabilityFactory = new class ($this->propertyAccessor, $this->typeResolver, DefaultField::fromBoolean($defaultField), $customReadCallback) {
            /**
             * @var null|callable(TEntity): (simple_primitive|array<int|string, mixed>|null)
             */
            private $customReadCallback;

            /**
             * @param null|callable(TEntity): (simple_primitive|array<int|string, mixed>|null) $customReadCallback
             */
            public function __construct(
                protected readonly PropertyAccessorInterface $propertyAccessor,
                protected readonly AttributeTypeResolverInterface $typeResolver,
                protected readonly DefaultField $defaultField,
                callable $customReadCallback = null
            ) {
                $this->customReadCallback = $customReadCallback;
            }

            /**
             * @param non-empty-string $name
             * @param non-empty-list<non-empty-string> $propertyPath
             * @param class-string<TEntity> $entityClass
             *
             * @return AttributeReadabilityInterface<TEntity>
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass): AttributeReadabilityInterface
            {
                return null === $this->customReadCallback
                    ? new PathAttributeReadability(
                        $entityClass,
                        $propertyPath,
                        $this->defaultField,
                        $this->propertyAccessor,
                        $this->typeResolver
                    ) : new CallbackAttributeReadability(
                        $this->defaultField,
                        $this->customReadCallback,
                        $this->typeResolver
                    );
            }
        };

        return $this;
    }

    public function updatable(array $entityConditions = [], callable $updateCallback = null): AttributeConfigBuilderInterface
    {
        return null === $updateCallback
            ? $this->addPathUpdateBehavior($entityConditions)
            : $this->addUpdateBehavior(
                new CallbackAttributeSetBehaviorFactory($entityConditions, $updateCallback, OptionalField::YES)
            );
    }

    public function build(): AttributeConfigInterface
    {
        $readability = $this->getReadability();
        $updateBehaviors = $this->getUpdateBehaviors();
        $postConstructorBehaviors = $this->getPostConstructorBehaviors();
        $constructorBehaviors = $this->getConstructorBehaviors();
        $filterLink = $this->getFilterLink();
        $sortLink = $this->getSortLink();

        return new DtoAttributeConfig(
            $readability,
            $updateBehaviors,
            $postConstructorBehaviors,
            $constructorBehaviors,
            $filterLink,
            $sortLink
        );
    }

    public function setNonReadable(): self
    {
        $this->readabilityFactory = null;

        return $this;
    }

    /**
     * @return list<PropertySetBehaviorInterface<TEntity>>
     */
    protected function getPostConstructorBehaviors(): array
    {
        return array_map(fn (
            PropertyUpdatabilityFactoryInterface $factory
        ): PropertySetBehaviorInterface => $factory(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass
        ), $this->postConstructorBehaviorFactories);
    }

    /**
     * @return list<ConstructorBehaviorInterface>
     */
    protected function getConstructorBehaviors(): array
    {
        return array_map(fn (callable $factory): ConstructorBehaviorInterface => $factory(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass
        ), $this->constructorBehaviorFactories);
    }

    /**
     * @return list<PropertyUpdatabilityInterface<TCondition, TEntity>>
     */
    protected function getUpdateBehaviors(): array
    {
        return array_map(fn (
            PropertyUpdatabilityFactoryInterface $factory
        ): PropertyUpdatabilityInterface => $factory(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass
        ), $this->updateBehaviorFactories);
    }

    /**
     * @return AttributeReadabilityInterface<TEntity>|null
     */
    protected function getReadability(): ?AttributeReadabilityInterface
    {
        return ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass);
    }

    protected function getFilterLink(): ?PropertyLinkInterface
    {
        return $this->filterable ? new NonRelationshipLink($this->getPropertyPath()) : null;
    }

    protected function getSortLink(): ?PropertyLinkInterface
    {
        return $this->sortable ? new NonRelationshipLink($this->getPropertyPath()) : null;
    }
}
