<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use EDT\Querying\Contracts\PathsBasedInterface;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 *
 * @template-extends AbstractCondition<TCondition>
 * @template-implements ValueIndependentConditionInterface<TCondition>
 */
class IsNull extends AbstractCondition implements ValueIndependentConditionInterface
{
    public const OPERATOR = 'IS NULL';

    public function getOperator(): string
    {
        return self::OPERATOR;
    }

    public function transform(?array $path): PathsBasedInterface
    {
        Assert::notNull($path);

        return $this->conditionFactory->propertyIsNull($path);
    }
}
