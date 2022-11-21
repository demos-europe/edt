<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 */
class Initializability
{
    protected bool $requiredForEntityCreation;

    /**
     * @var list<TCondition>
     */
    private array $conditions;

    /**
     * @param list<TCondition> $conditions
     */
    public function __construct(array $conditions, bool $requiredForCreation)
    {
        $this->conditions = $conditions;
        $this->requiredForEntityCreation = $requiredForCreation;
    }

    public function isRequiredForEntityCreation(): bool
    {
        return $this->requiredForEntityCreation;
    }

    /**
     * @return list<TCondition>
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
}
