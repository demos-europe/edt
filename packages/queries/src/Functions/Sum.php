<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends MultiFunction<numeric>
 */
class Sum extends MultiFunction
{
    /**
     * @param FunctionInterface<numeric> $firstAddend
     * @param FunctionInterface<numeric> $secondAddend
     * @param FunctionInterface<numeric> ...$additionalAddends
     */
    public function __construct(FunctionInterface $firstAddend, FunctionInterface $secondAddend, FunctionInterface ...$additionalAddends)
    {
        parent::__construct(
            static function (...$addends) {
                return array_sum($addends);
            },
            $firstAddend,
            $secondAddend,
            ...$additionalAddends
        );
    }
}
