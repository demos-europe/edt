<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

/**
 * @template TCondition
 */
interface ValueIndependentConditionInterface extends ConditionInterface
{
    /**
     * @param non-empty-list<non-empty-string>|null $path
     *
     * @return TCondition
     */
    public function transform(?array $path);
}
