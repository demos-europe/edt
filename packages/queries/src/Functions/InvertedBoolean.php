<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionException;
use function is_bool;

/**
 * @template-extends AbstractSingleFunction<bool, bool>
 *
 * @internal this implementation is not usable for to-many relationships
 */
class InvertedBoolean extends AbstractSingleFunction
{
    public function apply(array $propertyValues): bool
    {
        return !$this->getOnlyFunction()->apply($propertyValues);
    }
}
