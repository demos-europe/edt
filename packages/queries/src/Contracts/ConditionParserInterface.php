<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

interface ConditionParserInterface
{
    /**
     * @return FunctionInterface<bool>
     */
    public function parseCondition(array $input): FunctionInterface;
}
