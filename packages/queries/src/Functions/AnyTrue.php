<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Utilities\Iterables;
use function count;

/**
 * @template-extends AbstractFunction<bool, bool>
 */
class AnyTrue extends AbstractFunction
{
    public function apply(array $propertyValues): bool
    {
        $nestedPropertyValues = $this->unflatPropertyValues($propertyValues);
        $count = count($nestedPropertyValues);
        Iterables::assertCount($count, $this->functions);
        for ($i = 0; $i < $count; $i++) {
            $condition = $this->functions[$i];
            $propertyValues = $nestedPropertyValues[$i];
            if ($condition->apply($propertyValues)) {
                return true;
            }
        }

        return false;
    }
}
