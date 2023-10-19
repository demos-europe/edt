<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\PropertyConfig\AttributeConfigInterface;
use EDT\JsonApi\PropertyConfig\DtoAttributeConfig;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\PropertyPaths\NonRelationshipLink;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\CallbackAttributeReadability;
use EDT\Wrapping\PropertyBehavior\Attribute\Factory\AttributeConstructorBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Attribute\Factory\CallbackAttributeSetBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Attribute\Factory\PathAttributeSetBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Attribute\PathAttributeReadability;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityFactoryInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityInterface;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements AttributeConfigBuilderInterface<TCondition, TEntity>
 * @template-implements BuildableInterface<AttributeConfigInterface<TCondition, TEntity>>
 */
class AttributeConfigBuilder
    extends AbstractPropertyConfigBuilder
    implements AttributeConfigBuilderInterface, BuildableInterface
{
    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>): AttributeReadabilityInterface<TEntity>
     */
    protected $readabilityFactory;

    /**
     * @var list<PropertyUpdatabilityFactoryInterface<TCondition>>
     */
    protected array $postConstructorBehaviorFactories = [];

    /**
     * @var list<PropertyUpdatabilityFactoryInterface<TCondition>>
     */
    protected array $updateBehaviorFactories = [];

    /**
     * @var list<ConstructorBehaviorFactoryInterface>
     */
    protected array $constructorBehaviorFactories = [];

    /**
     * @param non-empty-string $name
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        string $name,
        protected readonly string $entityClass,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver
    ) {
        parent::__construct($name);
        Assert::notSame($this->name, ContentField::ID);
        Assert::notSame($this->name, ContentField::TYPE);
    }

    /**
     * @return $this
     */
    public function creatable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null
    ): self {
        if ($constructorArgument) {
            $this->addConstructorBehavior(
                new AttributeConstructorBehaviorFactory(
                    $customConstructorArgumentName,
                    null
                )
            );
        }

        $this->addPostConstructorBehavior(null === $postConstructorCallback
            ? new PathAttributeSetBehaviorFactory($this->propertyAccessor, [], $optionalAfterConstructor)
            : new CallbackAttributeSetBehaviorFactory([], $postConstructorCallback, $optionalAfterConstructor)
        );

        return $this;
    }

    /**
     * @param bool $defaultField the field is to be returned in responses by default
     * @param null|callable(TEntity): (simple_primitive|array<int|string, mixed>|null) $customReadCallback to be set if this property needs special handling when read
     *
     * @return $this
     */
    public function readable(bool $defaultField = false, callable $customReadCallback = null): self
    {
        $this->readabilityFactory = new class ($this->propertyAccessor, $this->typeResolver, $defaultField, $customReadCallback) {
            /**
             * @var null|callable(TEntity): (simple_primitive|array<int|string, mixed>|null)
             */
            private $customReadCallback;

            /**
             * @param null|callable(TEntity): (simple_primitive|array<int|string, mixed>|null) $customReadCallback
             */
            public function __construct(
                protected readonly PropertyAccessorInterface $propertyAccessor,
                protected readonly AttributeTypeResolver $typeResolver,
                protected readonly bool $defaultField,
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

    /**
     * @return $this
     */
    public function updatable(array $entityConditions = [], callable $updateCallback = null): AttributeConfigBuilderInterface
    {
        return $this->addUpdateBehavior(null === $updateCallback
            ? new PathAttributeSetBehaviorFactory($this->propertyAccessor, $entityConditions, true)
            : new CallbackAttributeSetBehaviorFactory($entityConditions, $updateCallback, true)
        );
    }

    public function build(): AttributeConfigInterface
    {
        $postConstructorBehaviors = array_map(fn (
            PropertyUpdatabilityFactoryInterface $factory
        ): PropertySetBehaviorInterface => $factory->createUpdatability(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass
        ), $this->postConstructorBehaviorFactories);

        $constructorBehaviors = array_map(fn (
            ConstructorBehaviorFactoryInterface $factory
        ): ConstructorBehaviorInterface => $factory->createConstructorBehavior(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass
        ), $this->constructorBehaviorFactories);

        $updateBehaviors = array_map(fn (
            PropertyUpdatabilityFactoryInterface $factory
        ): PropertyUpdatabilityInterface => $factory->createUpdatability(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass
        ), $this->updateBehaviorFactories);


        return new DtoAttributeConfig(
            ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass),
            $updateBehaviors,
            $postConstructorBehaviors,
            $constructorBehaviors,
            $this->filterable ? new NonRelationshipLink($this->getPropertyPath()) : null,
            $this->sortable ? new NonRelationshipLink($this->getPropertyPath()) : null
        );
    }

    public function addConstructorBehavior(ConstructorBehaviorFactoryInterface $behaviorFactory): AttributeConfigBuilderInterface
    {
        $this->constructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    public function addPostConstructorBehavior(PropertyUpdatabilityFactoryInterface $behaviorFactory): AttributeConfigBuilderInterface
    {
        $this->postConstructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    public function addUpdateBehavior(PropertyUpdatabilityFactoryInterface $behaviorFactory): AttributeConfigBuilderInterface
    {
        $this->updateBehaviorFactories[] = $behaviorFactory;

        return $this;
    }
}
