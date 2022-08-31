<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends MultiFunction<numeric>
 */
class Product extends MultiFunction
{
    /**
     * @param FunctionInterface<numeric> $firstFactor
     * @param FunctionInterface<numeric> $secondFactor
     * @param FunctionInterface<numeric> ...$additionalFactors
     */
    public function __construct(FunctionInterface $firstFactor, FunctionInterface $secondFactor, FunctionInterface ...$additionalFactors)
    {
        parent::__construct(
            static function (...$factors) {
                return array_product($factors);
            },
            $firstFactor,
            $secondFactor,
            ...$additionalFactors
        );
    }
}
