<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use function in_array;

/**
 * @template-extends MultiFunction<bool>
 */
class OneOf extends MultiFunction
{
    /**
     * @template V
     * @param FunctionInterface<array<V>> $contains
     * @param FunctionInterface<V> $contained
     */
    public function __construct(FunctionInterface $contains, FunctionInterface $contained)
    {
        parent::__construct(
            static function (array $contains, $contained): bool {
                return in_array($contained, $contains, true);
            },
            $contains,
            $contained
        );
    }
}
