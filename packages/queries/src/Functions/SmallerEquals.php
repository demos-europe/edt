<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends MultiFunction<bool>
 */
class SmallerEquals extends MultiFunction
{
    /**
     * @param FunctionInterface<mixed> $left
     * @param FunctionInterface<mixed> $right
     */
    public function __construct(FunctionInterface $left, FunctionInterface $right)
    {
        parent::__construct(
            static function ($leftValue, $rightValue): bool {
                return $leftValue <= $rightValue;
            },
            $left,
            $right
        );
    }
}
