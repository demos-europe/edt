<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use Countable;
use function count;

/**
 * @template-extends AbstractSingleFunction<int, Countable|array<int|string, mixed>>
 */
class Size extends AbstractSingleFunction
{
    public function apply(array $propertyValues): int
    {
        return count($this->getOnlyFunction()->apply($propertyValues));
    }
}

