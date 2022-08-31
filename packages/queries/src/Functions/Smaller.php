<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends MultiFunction<bool>
 */
class Smaller extends MultiFunction
{
    /**
     * @param FunctionInterface<mixed> $left
     * @param FunctionInterface<mixed> $right
     */
    public function __construct(FunctionInterface $left, FunctionInterface $right)
    {
        parent::__construct(
            static function ($left, $right): bool {
                return $left < $right;
            },
            $left,
            $right
        );
    }
}
