<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\JsonApi\RequestHandling\Body\CreationRequestBody;
use EDT\JsonApi\Requests\PropertyUpdaterTrait;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\ConstructorParameterInterface;
use EDT\Wrapping\Properties\InitializabilityCollection;

class EntityCreator
{
    use PropertyUpdaterTrait;

    /**
     * @template TEntity of object
     *
     * @param class-string<TEntity> $entityClass
     * @param InitializabilityCollection<TEntity, PathsBasedInterface, PathsBasedInterface> $initializabilities
     *
     * @return TEntity
     */
    public function createEntity(string $entityClass, InitializabilityCollection $initializabilities, CreationRequestBody $requestBody): object
    {
        $orderedConstructorArguments = $initializabilities->getOrderedConstructorArguments();
        $constructorArguments = array_map(
            static fn (ConstructorParameterInterface $constructorParameter): mixed => $constructorParameter->getValue($requestBody),
            $orderedConstructorArguments
        );

        return new $entityClass(...$constructorArguments);
    }
}
