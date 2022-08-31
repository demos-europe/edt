<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * @template I
 */
interface ConditionParserInterface
{
    /**
     * @param I $condition
     *
     * @return FunctionInterface<bool>
     */
    public function parseCondition($condition): FunctionInterface;
}
