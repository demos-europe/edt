<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\PropertyConfig\DtoIdentifierConfig;
use EDT\JsonApi\PropertyConfig\IdentifierConfigInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\PropertyPaths\NonRelationshipLink;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\CallbackIdentifierReadability;
use EDT\Wrapping\PropertyBehavior\Identifier\Factory\DataProvidedIdentifierConstructorBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Identifier\Factory\IdentifierConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\Factory\IdentifierPostConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\Factory\PathIdentifierPostConstructorBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierPostConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\PathIdentifierReadability;

/**
 * @template TEntity of object
 *
 * @template-implements IdentifierConfigBuilderInterface<TEntity>
 */
class IdentifierConfigBuilder extends AbstractPropertyConfigBuilder implements IdentifierConfigBuilderInterface
{
    /**
     * @var null|callable(non-empty-list<non-empty-string>, class-string<TEntity>): IdentifierReadabilityInterface<TEntity>
     */
    protected $readabilityFactory;

    /**
     * @var list<IdentifierPostConstructorBehaviorFactoryInterface<TEntity>>
     */
    protected array $postConstructorBehaviorFactories = [];

    /**
     * @var list<IdentifierConstructorBehaviorFactoryInterface<TEntity>>
     */
    protected array $constructorBehaviorFactories = [];

    /**
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver
    ) {
        parent::__construct(ContentField::ID);
    }

    /**
     * @param null|callable(TEntity): non-empty-string $customReadCallback to be set if this property needs special handling when read
     *
     * @return $this
     */
    public function readable(callable $customReadCallback = null): self
    {
        $this->readabilityFactory = new class ($this->propertyAccessor, $this->typeResolver, $customReadCallback) {
            /**
             * @var null|callable(TEntity): non-empty-string
             */
            private $customReadCallback;

            /**
             * @param null|callable(TEntity): non-empty-string $customReadCallback
             */
            public function __construct(
                protected readonly PropertyAccessorInterface $propertyAccessor,
                protected readonly AttributeTypeResolver $typeResolver,
                callable $customReadCallback = null
            ) {
                $this->customReadCallback = $customReadCallback;
            }

            /**
             * @param non-empty-list<non-empty-string> $propertyPath
             * @param class-string<TEntity> $entityClass
             *
             * @return IdentifierReadabilityInterface<TEntity>
             */
            public function __invoke(array $propertyPath, string $entityClass): IdentifierReadabilityInterface
            {
                return null === $this->customReadCallback
                    ? new PathIdentifierReadability(
                        $entityClass,
                        $propertyPath,
                        $this->propertyAccessor
                    ) : new CallbackIdentifierReadability($this->customReadCallback);
            }
        };

        return $this;
    }

    /**
     * @return IdentifierConfigInterface<TEntity>
     */
    public function build(): IdentifierConfigInterface
    {
        $postConstructorBehaviors = array_map(
            fn(
                IdentifierPostConstructorBehaviorFactoryInterface $factory
            ): IdentifierPostConstructorBehaviorInterface => $factory->createIdentifierPostConstructorBehavior(
                $this->getPropertyPath(),
                $this->entityClass
            ),
            $this->postConstructorBehaviorFactories
        );

        $constructorBehaviors = array_map(
            fn(
                IdentifierConstructorBehaviorFactoryInterface $factory
            ): ConstructorBehaviorInterface => $factory->createIdentifierConstructorBehavior(
                $this->getPropertyPath(),
                $this->entityClass
            ),
            $this->constructorBehaviorFactories
        );

        return new DtoIdentifierConfig(
            ($this->readabilityFactory ?? static fn () => null)($this->getPropertyPath(), $this->entityClass),
            $postConstructorBehaviors,
            $constructorBehaviors,
            $this->filterable ? new NonRelationshipLink($this->getPropertyPath()) : null,
            $this->sortable ? new NonRelationshipLink($this->getPropertyPath()) : null
        );
    }

    /**
     * @return $this
     */
    public function addConstructorBehavior(IdentifierConstructorBehaviorFactoryInterface $behaviorFactory): IdentifierConfigBuilderInterface
    {
        $this->constructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    /**
     * @return $this
     */
    public function addPostConstructorBehavior(IdentifierPostConstructorBehaviorFactoryInterface $behaviorFactory): IdentifierConfigBuilderInterface
    {
        $this->postConstructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    public function creatable(bool $optionalAfterConstructor = false, bool $constructorArgument = false, ?string $customConstructorArgumentName = null): IdentifierConfigBuilderInterface
    {
        if ($constructorArgument) {
            $this->addConstructorBehavior(
                new DataProvidedIdentifierConstructorBehaviorFactory($customConstructorArgumentName)
            );
        }

        $this->addPostConstructorBehavior(
            new PathIdentifierPostConstructorBehaviorFactory($optionalAfterConstructor, $this->propertyAccessor)
        );

        return $this;
    }
}
