<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
interface ConstructorParameterInterface extends PropertyConstrainingInterface
{
    /**
     * @param non-empty-string|null $entityId
     */
    public function getArgument(?string $entityId, EntityDataInterface $entityData): mixed;

    /**
     * @return non-empty-string The name of the constructor parameter, matching the actual argument name in the
     *                          constructors source code. It may be the same as an exposed property name, but should
     *                          not be confused with it.
     */
    public function getArgumentName(): string;
}
