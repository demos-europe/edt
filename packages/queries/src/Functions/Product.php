<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-implements FunctionInterface<numeric>
 */
class Product implements FunctionInterface
{
    use MultiFunctionTrait;

    /**
     * @param FunctionInterface<numeric> $firstFactor
     * @param FunctionInterface<numeric> $secondFactor
     * @param FunctionInterface<numeric> ...$additionalFactors
     */
    public function __construct(FunctionInterface $firstFactor, FunctionInterface $secondFactor, FunctionInterface ...$additionalFactors)
    {
        $this->setFunctions($firstFactor, $secondFactor, ...$additionalFactors);
        $this->callback = static function (...$factors) {
            return array_product($factors);
        };
    }
}
