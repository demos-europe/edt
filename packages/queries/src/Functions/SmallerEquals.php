<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-implements FunctionInterface<bool>
 */
class SmallerEquals implements FunctionInterface
{
    use MultiFunctionTrait;

    /**
     * @param FunctionInterface<mixed> $left
     * @param FunctionInterface<mixed> $right
     */
    public function __construct(FunctionInterface $left, FunctionInterface $right)
    {
        $this->setFunctions($left, $right);
        $this->callback = static function ($leftValue, $rightValue): bool {
            return $leftValue <= $rightValue;
        };
    }
}
