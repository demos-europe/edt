<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends MultiFunction<bool>
 */
class BetweenInclusive extends MultiFunction
{
    /**
     * @param FunctionInterface<numeric|null> $min
     * @param FunctionInterface<numeric|null> $max
     * @param FunctionInterface<numeric|null> $value
     */
    public function __construct(FunctionInterface $min, FunctionInterface $max, FunctionInterface $value)
    {
        parent::__construct(
            static function ($min, $max, $value): bool {
                if (null === $min || null === $max || null === $value) {
                    return false;
                }
                return $value >= $min && $value <= $max;
            },
            $min,
            $max,
            $value
        );
    }
}
