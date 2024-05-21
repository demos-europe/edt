<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 */
abstract class AbstractCondition implements ConditionInterface
{
    /**
     * @param ConditionFactoryInterface<TCondition> $conditionFactory
     */
    public function __construct(protected readonly ConditionFactoryInterface $conditionFactory) {}

    public function getFormatConstraints(): array
    {
        // FIXME: remove here and implement in children
        return [];
    }
}
