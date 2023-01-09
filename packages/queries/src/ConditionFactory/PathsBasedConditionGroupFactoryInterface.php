<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 *
 * @template-extends ConditionGroupFactoryInterface<TCondition>
 */
interface PathsBasedConditionGroupFactoryInterface extends ConditionGroupFactoryInterface
{
}
