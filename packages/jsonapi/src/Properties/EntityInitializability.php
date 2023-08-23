<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\ConstructorParameterInterface;
use EDT\Wrapping\Properties\EntityDataInterface;
use EDT\Wrapping\Properties\AbstractEntityModifier;
use EDT\Wrapping\Properties\PropertyConstrainingInterface;
use EDT\Wrapping\Properties\PropertySetabilityInterface;
use InvalidArgumentException;
use ReflectionParameter;
use Webmozart\Assert\Assert;

/**
 * @template TEntity of object
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class EntityInitializability extends AbstractEntityModifier
{
    /**
     * @var list<ConstructorParameterInterface<TCondition, TSorting>|null>
     */
    protected readonly array $orderedConstructorParameters;

    /**
     * @param class-string<TEntity> $entityClass
     * @param list<ConstructorParameterInterface<TCondition, TSorting>> $constructorParameters
     * @param list<ReflectionParameter> $reflectionConstructorParameters
     * @param list<PropertySetabilityInterface<TCondition, TEntity>> $setabilities
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly array $reflectionConstructorParameters,
        array $constructorParameters,
        protected readonly array $setabilities
    ) {
        $lookupTable = [];
        foreach ($constructorParameters as $constructorParameter) {
            $argumentName = $constructorParameter->getArgumentName();
            Assert::keyNotExists($lookupTable, $argumentName);
            $lookupTable[$argumentName] = $constructorParameter;
        }

        $this->orderedConstructorParameters = array_map(
            static fn(
                ReflectionParameter $reflectionParameter
            ): ?ConstructorParameterInterface => $lookupTable[$reflectionParameter->getName()] ?? null,
            $this->reflectionConstructorParameters
        );
    }

    /**
     * @param list<mixed> $constructorArguments
     *
     * @return TEntity
     */
    public function initializeEntity(array $constructorArguments): object
    {
        return new $this->entityClass(...$constructorArguments);
    }

    /**
     * @param non-empty-string $entityId
     * @param TEntity $entity
     */
    // FIXME: respect entityId
    public function fillEntity(?string $entityId, object $entity, EntityDataInterface $entityData): bool
    {
        return $this->getSetabilitiesSideEffect($this->setabilities, $entity, $entityData);
    }

    protected function getParameterConstrains(): array
    {
        return array_merge(
            array_filter(
                $this->orderedConstructorParameters,
                static fn (?PropertyConstrainingInterface $param): bool => null !== $param
            ),
            $this->setabilities
        );
    }

    /**
     * @param non-empty-string|null $entityId
     *
     * @return list<mixed>
     */
    // FIXME: must return side effect information
    public function getConstructorArguments(?string $entityId, EntityDataInterface $entityData): array
    {
        return array_map(
            static function (
                ReflectionParameter $reflectionParameter,
                ?ConstructorParameterInterface $constructorParameter
            ) use ($entityId, $entityData): mixed {
                // if no constructor parameter was given, fall back to default parameters
                if (null === $constructorParameter) {
                    return $reflectionParameter->isDefaultValueAvailable()
                        ? $reflectionParameter->getDefaultValue()
                        : throw new InvalidArgumentException('Missing constructor parameter');
                }

                return $constructorParameter->getArgument($entityId, $entityData);
            },
            $this->reflectionConstructorParameters,
            $this->orderedConstructorParameters
        );
    }
}
