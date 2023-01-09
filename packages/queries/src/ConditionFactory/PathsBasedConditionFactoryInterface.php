<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 *
 * @template-extends ConditionFactoryInterface<TCondition>
 */
interface PathsBasedConditionFactoryInterface extends ConditionFactoryInterface
{
}
