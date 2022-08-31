<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Utilities\Iterables;
use function count;

/**
 * @template-extends AbstractFunction<bool, mixed>
 */
class AnyEqual extends AbstractFunction
{
    public function apply(array $propertyValues): bool
    {
        $nestedPropertyValues = $this->unflatPropertyValues($propertyValues);
        $count = count($this->functions);
        Iterables::assertCount($count, $nestedPropertyValues);
        $evaluations = [];
        for ($i = 0; $i < $count; $i++) {
            $function = $this->functions[$i];
            $propertyValues = $nestedPropertyValues[$i];
            $newEvaluation = $function->apply($propertyValues);
            if (null !== $newEvaluation) {
                foreach ($evaluations as $evaluation) {
                    if ($evaluation === $newEvaluation) {
                        return true;
                    }
                    $evaluations[] = $newEvaluation;
                }
            }
        }

        return false;
    }
}
