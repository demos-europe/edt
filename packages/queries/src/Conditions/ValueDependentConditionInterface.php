<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use EDT\Querying\Contracts\PathException;

/**
 * @template TCondition
 */
interface ValueDependentConditionInterface extends ConditionInterface
{
    /**
     * @param non-empty-list<non-empty-string>|null $path
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function transform(?array $path, mixed $value);
}
