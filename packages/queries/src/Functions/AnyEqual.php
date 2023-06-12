<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use Webmozart\Assert\Assert;
use function count;
use function in_array;

/**
 * @template-extends AbstractFunction<bool, mixed>
 */
class AnyEqual extends AbstractFunction
{
    public function apply(array $propertyValues): bool
    {
        $nestedPropertyValues = $this->unflatPropertyValues($propertyValues);
        $functionsCount = count($this->functions);
        Assert::count($nestedPropertyValues, $functionsCount);

        $evaluations = [];
        for ($functionIndex = 0; $functionIndex < $functionsCount; $functionIndex++) {
            $propertyValues = $nestedPropertyValues[$functionIndex];
            $newEvaluationResult = $this->functions[$functionIndex]->apply($propertyValues);
            if (null !== $newEvaluationResult) { // `null`-values will not match any other value
                if (in_array($newEvaluationResult, $evaluations, true)) {
                    return true;
                }
                $evaluations[] = $newEvaluationResult;
            }
        }

        return false;
    }
}
