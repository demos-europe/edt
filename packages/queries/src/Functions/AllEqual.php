<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use Webmozart\Assert\Assert;
use function count;

/**
 * @template-extends AbstractFunction<bool, mixed|null>
 */
class AllEqual extends AbstractFunction
{
    public function apply(array $propertyValues): bool
    {
        $nestedPropertyValues = $this->unflatPropertyValues($propertyValues);
        $count = count($this->functions);
        Assert::count($nestedPropertyValues, $count);
        $evaluations = [];
        for ($functionIndex = 0; $functionIndex < $count; $functionIndex++) {
            $function = $this->functions[$functionIndex];
            $propertyValues = $nestedPropertyValues[$functionIndex];
            $newEvaluation = $function->apply($propertyValues);
            if (null === $newEvaluation) {
                return false;
            }
            foreach ($evaluations as $evaluation) {
                if ($evaluation !== $newEvaluation) {
                    return false;
                }
            }
            $evaluations[] = $newEvaluation;
        }

        return true;
    }
}
