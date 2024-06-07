<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\JsonApi\PropertyConfig\DtoIdentifierConfig;
use EDT\JsonApi\PropertyConfig\IdentifierConfigInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\PropertyPaths\NonRelationshipLink;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\CallbackIdentifierReadability;
use EDT\Wrapping\PropertyBehavior\Identifier\DataProvidedIdentifierConstructorBehavior;
use EDT\Wrapping\PropertyBehavior\Identifier\Factory\IdentifierConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\Factory\IdentifierPostConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierPostConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\PathIdentifierPostConstructorBehavior;
use EDT\Wrapping\PropertyBehavior\Identifier\PathIdentifierReadability;
use InvalidArgumentException;
use EDT\Wrapping\Utilities\AttributeTypeResolverInterface;

/**
 * @template TEntity of object
 *
 * @template-implements IdentifierConfigBuilderInterface<TEntity>
 */
class IdentifierConfigBuilder implements IdentifierConfigBuilderInterface
{
    use AliasTrait;
    use FilterTrait;
    use SortTrait;

    /**
     * @var null|callable(non-empty-list<non-empty-string>, class-string<TEntity>): IdentifierReadabilityInterface<TEntity>
     */
    protected $readabilityFactory;

    /**
     * @var list<IdentifierPostConstructorBehaviorFactoryInterface<TEntity>>
     */
    protected array $postConstructorBehaviorFactories = [];

    /**
     * @var list<IdentifierConstructorBehaviorFactoryInterface>
     */
    protected array $constructorBehaviorFactories = [];

    /**
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolverInterface $typeResolver
    ) {}

    /**
     * @return non-empty-list<non-empty-string>
     */
    protected function getPropertyPath(): array
    {
        return $this->aliasedPath ?? [ContentField::ID];
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return ContentField::ID;
    }

    public function setReadableByPath(): self
    {
        return $this->readable();
    }

    public function setReadableByCallable(callable $behavior): self
    {
        return $this->readable($behavior);
    }

    public function readable(callable $customReadCallback = null): self
    {
        $this->readabilityFactory = new class($this->propertyAccessor, $this->typeResolver, $customReadCallback) {
            /**
             * @var null|callable(TEntity): non-empty-string
             */
            private $customReadCallback;

            /**
             * @param null|callable(TEntity): non-empty-string $customReadCallback
             */
            public function __construct(
                protected readonly PropertyAccessorInterface $propertyAccessor,
                protected readonly AttributeTypeResolverInterface $typeResolver,
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
            fn(IdentifierPostConstructorBehaviorFactoryInterface $factory): IdentifierPostConstructorBehaviorInterface => $factory(
                $this->getPropertyPath(),
                $this->entityClass
            ),
            $this->postConstructorBehaviorFactories
        );

        $constructorBehaviors = array_map(
            fn(IdentifierConstructorBehaviorFactoryInterface $factory): ConstructorBehaviorInterface => $factory(
                $this->getPropertyPath(),
                $this->entityClass
            ),
            $this->constructorBehaviorFactories
        );

        if (null === $this->readabilityFactory) {
            throw new InvalidArgumentException('No readability set for the identifier. But the identifier must always be readable.');
        }
        $readability = ($this->readabilityFactory)($this->getPropertyPath(), $this->entityClass);

        return new DtoIdentifierConfig(
            $readability,
            $postConstructorBehaviors,
            $constructorBehaviors,
            $this->filterable ? new NonRelationshipLink($this->getPropertyPath()) : null,
            $this->sortable ? new NonRelationshipLink($this->getPropertyPath()) : null
        );
    }

    public function initializable(bool $optionalAfterConstructor = false, bool $constructorArgument = false, ?string $customConstructorArgumentName = null): IdentifierConfigBuilderInterface
    {
        if ($constructorArgument) {
            $this->addConstructorBehavior(
                DataProvidedIdentifierConstructorBehavior::createFactory($customConstructorArgumentName, OptionalField::NO, null)
            );
        }

        return $this->addPathCreationBehavior(OptionalField::fromBoolean($optionalAfterConstructor));
    }

    public function addConstructorBehavior(IdentifierConstructorBehaviorFactoryInterface $behaviorFactory): self
    {
        $this->constructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    public function addCreationBehavior(IdentifierPostConstructorBehaviorFactoryInterface $behaviorFactory): self
    {
        $this->postConstructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    public function addPathCreationBehavior(OptionalField $optional = OptionalField::NO, array $entityConditions = []): self
    {
        return $this->addCreationBehavior(
            PathIdentifierPostConstructorBehavior::createFactory($optional, $this->propertyAccessor, $entityConditions)
        );
    }

    public function removeAllCreationBehaviors(): self
    {
        $this->constructorBehaviorFactories = [];
        $this->postConstructorBehaviorFactories = [];

        return $this;
    }

    public function addPostConstructorBehavior(IdentifierPostConstructorBehaviorFactoryInterface $behaviorFactory): IdentifierConfigBuilderInterface
    {
        return $this->addCreationBehavior($behaviorFactory);
    }
}
