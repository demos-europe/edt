<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-implements FunctionInterface<numeric>
 */
class Sum implements FunctionInterface
{
    use MultiFunctionTrait;

    /**
     * @param FunctionInterface<numeric> $firstAddend
     * @param FunctionInterface<numeric> $secondAddend
     * @param FunctionInterface<numeric> ...$additionalAddends
     */
    public function __construct(FunctionInterface $firstAddend, FunctionInterface $secondAddend, FunctionInterface ...$additionalAddends)
    {
        $this->setFunctions($firstAddend, $secondAddend, ...$additionalAddends);
        $this->callback = static function (...$addends) {
            return array_sum($addends);
        };
    }
}
