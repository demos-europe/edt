<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use function in_array;

/**
 * @template-implements FunctionInterface<bool>
 */
class OneOf implements FunctionInterface
{
    use MultiFunctionTrait;

    /**
     * @template V
     * @param FunctionInterface<array<V>> $contains
     * @param FunctionInterface<V> $contained
     */
    public function __construct(FunctionInterface $contains, FunctionInterface $contained)
    {
        $this->setFunctions($contains, $contained);
        $this->callback = static function (array $contains, $contained): bool {
            return in_array($contained, $contains, true);
        };
    }
}
