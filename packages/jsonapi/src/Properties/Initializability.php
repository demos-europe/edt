<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 *
 * @template-implements PropertyInitializabilityInterface<TCondition>
 */
class Initializability implements PropertyInitializabilityInterface
{
    /**
     * @param list<TCondition> $entityConditions
     */
    public function __construct(
        protected readonly array $entityConditions,
        protected readonly bool $optional
    ) {}

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function getEntityConditions(): array
    {
        return $this->entityConditions;
    }
}
