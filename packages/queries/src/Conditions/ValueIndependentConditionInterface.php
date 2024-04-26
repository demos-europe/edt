<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 *
 * @template-extends ConditionInterface<TCondition>
 */
interface ValueIndependentConditionInterface extends ConditionInterface
{
    /**
     * @param non-empty-list<non-empty-string>|null $path
     *
     * @return TCondition
     */
    public function transform(?array $path): PathsBasedInterface;
}
