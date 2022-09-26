<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * @template I
 * @template F of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface ConditionParserInterface
{
    /**
     * @param I $condition
     *
     * @return F
     */
    public function parseCondition($condition): PathsBasedInterface;
}
