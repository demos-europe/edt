<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

class EntityCreator
{
    /**
     * @template T of object
     *
     * @param class-string<T> $entityClass
     * @param list<mixed> $constructorArguments
     *
     * @return T
     */
    public function createEntity(string $entityClass, array $constructorArguments): object
    {
        return new $entityClass(...$constructorArguments);
    }
}
