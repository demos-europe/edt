<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * TODO: evaluate if really needed, if not, remove
 *
 * @template TFilterCondition
 * @template TCondition of PathsBasedInterface
 */
interface ConditionParserInterface
{
    /**
     * @param TFilterCondition $condition
     *
     * @return TCondition
     */
    public function parseCondition($condition): PathsBasedInterface;
}
