<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

/**
 * @template-extends AbstractMultiFunction<int|float, numeric, list<mixed>>
 */
class Product extends AbstractMultiFunction
{
    protected function reduce(array $functionResults)
    {
        return array_product($functionResults);
    }
}
