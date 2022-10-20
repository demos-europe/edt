<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 *
 * @template-extends ConditionFactoryInterface<TCondition>
 */
interface PathsBasedConditionFactoryInterface extends ConditionFactoryInterface
{
}
