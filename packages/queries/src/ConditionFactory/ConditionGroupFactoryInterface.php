<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

/**
 * @template TCondition
 */
interface ConditionGroupFactoryInterface
{
    /**
     * @param TCondition $firstCondition
     * @param TCondition ...$additionalConditions
     *
     * @return TCondition
     */
    public function allConditionsApply($firstCondition, ...$additionalConditions);

    /**
     * @param TCondition $firstCondition
     * @param TCondition ...$additionalConditions
     *
     * @return TCondition
     */
    public function anyConditionApplies($firstCondition, ...$additionalConditions);
}
