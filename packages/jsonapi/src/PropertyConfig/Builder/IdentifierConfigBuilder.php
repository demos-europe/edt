<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\PropertyConfig\DtoIdentifierConfig;
use EDT\JsonApi\PropertyConfig\IdentifierConfigInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\PropertyPaths\NonRelationshipLink;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\PropertyBehavior\ConstructorParameterInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\CallbackIdentifierReadability;
use EDT\Wrapping\PropertyBehavior\Identifier\DataProvidedIdentifierConstructorParameter;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierPostInstantiabilityInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\PathIdentifierReadability;
use EDT\Wrapping\PropertyBehavior\Identifier\PathIdentifierPostInstantiability;

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
     * @var null|callable(non-empty-list<non-empty-string>, class-string<TEntity>): IdentifierPostInstantiabilityInterface<TEntity>
     */
    protected $postInstantiabilityFactory;

    /**
     * @var null|callable(non-empty-list<non-empty-string>, class-string<TEntity>): ConstructorParameterInterface
     */
    protected $instantiabilityFactory;

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
     * @return $this
     */
    public function instantiable(
        bool $postInstantiationSetting,
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
                 * @param non-empty-list<non-empty-string> $propertyPath
                 * @param class-string<TEntity> $entityClass
                 */
                public function __invoke(array $propertyPath, string $entityClass): ConstructorParameterInterface
                {
                    return new DataProvidedIdentifierConstructorParameter(
                        $this->argumentName ?? ContentField::ID
                    );
                }
            };
        }

        if ($postInstantiationSetting) {
            $this->postInstantiabilityFactory = new class ($this->propertyAccessor) {
                public function __construct(
                    protected readonly PropertyAccessorInterface $propertyAccessor,
                ) {}

                /**
                 * @param non-empty-list<non-empty-string> $propertyPath
                 * @param class-string<TEntity> $entityClass
                 *
                 * @return IdentifierPostInstantiabilityInterface<TEntity>
                 */
                public function __invoke(array $propertyPath, string $entityClass): IdentifierPostInstantiabilityInterface
                {
                    return new PathIdentifierPostInstantiability(
                        $entityClass,
                        $propertyPath,
                        $this->propertyAccessor,
                        true
                    );
                }
            };
        }

        return $this;
    }

    /**
     * @return IdentifierConfigInterface<TEntity>
     */
    public function build(): IdentifierConfigInterface
    {
        return new DtoIdentifierConfig(
            ($this->readabilityFactory ?? static fn () => null)($this->getPropertyPath(), $this->entityClass),
            ($this->postInstantiabilityFactory ?? static fn () => null)($this->getPropertyPath(), $this->entityClass),
            ($this->instantiabilityFactory ?? static fn () => null)($this->getPropertyPath(), $this->entityClass),
            $this->filterable ? new NonRelationshipLink($this->getPropertyPath()) : null,
            $this->sortable ? new NonRelationshipLink($this->getPropertyPath()) : null
        );
    }
}
