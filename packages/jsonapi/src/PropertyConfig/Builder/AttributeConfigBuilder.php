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
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeConstructorParameter;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\CallbackAttributeReadability;
use EDT\Wrapping\PropertyBehavior\Attribute\CallbackAttributeSetability;
use EDT\Wrapping\PropertyBehavior\Attribute\PathAttributeReadability;
use EDT\Wrapping\PropertyBehavior\Attribute\PathAttributeSetability;
use EDT\Wrapping\PropertyBehavior\ConstructorParameterInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetabilityInterface;
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
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>): PropertySetabilityInterface<TEntity>
     */
    protected $postInstantiabilityFactory;

    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>): PropertyUpdatabilityInterface<TCondition, TEntity>
     */
    protected $updatabilityFactory;

    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>): ConstructorParameterInterface
     */
    protected $instantiabilityFactory;

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
     * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $postInstantiationCallback
     * @param non-empty-string|null $argumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     *
     * @return $this
     */
    public function instantiable(
        bool $optional = false,
        callable $postInstantiationCallback = null,
        bool $argument = false,
        ?string $argumentName = null
    ): self {
        if ($argument) {
            $this->instantiabilityFactory = new class ($argumentName) {
                /**
                 * @param non-empty-string|null $argumentName
                 */
                public function __construct(
                    protected readonly ?string $argumentName
                ) {}

                /**
                 * @param non-empty-string $name
                 * @param non-empty-list<non-empty-string> $propertyPath
                 * @param class-string<TEntity> $entityClass
                 */
                public function __invoke(string $name, array $propertyPath, string $entityClass): ConstructorParameterInterface
                {
                    return new AttributeConstructorParameter(
                        $name,
                        $this->argumentName ?? $name
                    );
                }
            };
        }

        $this->postInstantiabilityFactory = new class (
            $this->propertyAccessor,
            $optional,
            $postInstantiationCallback
        ) {
            /**
             * @var null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool
             */
            private $postInstantiationCallback;

            /**
             * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $postInstantiationCallback
             */
            public function __construct(
                protected readonly PropertyAccessorInterface $propertyAccessor,
                protected readonly bool $optional,
                callable $postInstantiationCallback = null
            ) {
                $this->postInstantiationCallback = $postInstantiationCallback;
            }

            /**
             * @param non-empty-string $name
             * @param non-empty-list<non-empty-string> $propertyPath
             * @param class-string<TEntity> $entityClass
             * @return PropertySetabilityInterface<TEntity>
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass): PropertySetabilityInterface
            {
                return null === $this->postInstantiationCallback
                    ? new PathAttributeSetability(
                        $name,
                        $entityClass,
                        [],
                        $propertyPath,
                        $this->propertyAccessor,
                        $this->optional
                    )
                    : new CallbackAttributeSetability(
                        $name,
                        [],
                        $this->postInstantiationCallback,
                        $this->optional
                    );
            }
        };

        return $this;
    }

    /**
     * @param list<TCondition> $entityConditions
     * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $updateCallback
     *
     * @return $this
     */
    public function updatable(array $entityConditions = [], callable $updateCallback = null): self
    {
        $this->updatabilityFactory = new class (
            $this->propertyAccessor,
            $entityConditions,
            $updateCallback
        ) {
            /**
             * @var null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool
             */
            private $updateCallback;

            /**
             * @param list<TCondition> $entityConditions
             * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $updateCallback
             */
            public function __construct(
                protected readonly PropertyAccessorInterface $propertyAccessor,
                protected readonly array $entityConditions,
                callable $updateCallback = null
            ) {
                $this->updateCallback = $updateCallback;
            }

            /**
             * @param non-empty-string $name
             * @param non-empty-list<non-empty-string> $propertyPath
             * @param class-string<TEntity> $entityClass
             *
             * @return PropertyUpdatabilityInterface<TCondition, TEntity>
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass): PropertyUpdatabilityInterface
            {
                return null === $this->updateCallback
                    ? new PathAttributeSetability(
                        $name,
                        $entityClass,
                        $this->entityConditions,
                        $propertyPath,
                        $this->propertyAccessor,
                        true
                    )
                    : new CallbackAttributeSetability(
                        $name,
                        $this->entityConditions,
                        $this->updateCallback,
                        true
                    );
            }
        };

        return $this;
    }

    public function build(): AttributeConfigInterface
    {
        return new DtoAttributeConfig(
            ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass),
            ($this->updatabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass),
            ($this->postInstantiabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass),
            ($this->instantiabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass),
            $this->filterable ? new NonRelationshipLink($this->getPropertyPath()) : null,
            $this->sortable ? new NonRelationshipLink($this->getPropertyPath()) : null
        );
    }
}
