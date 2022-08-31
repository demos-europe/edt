<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use function is_string;

/**
 * @template-extends AbstractSingleFunction<string|null, string|null>
 */
class UpperCase extends AbstractSingleFunction
{
    public function apply(array $propertyValues): ?string
    {
        $baseFunctionResult = $this->getOnlyFunction()->apply($propertyValues);
        if (!is_string($baseFunctionResult)) {
            return null;
        }
        return mb_strtoupper($baseFunctionResult);
    }
}
