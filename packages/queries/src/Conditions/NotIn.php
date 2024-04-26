<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use EDT\Querying\Contracts\PathsBasedInterface;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 *
 * @template-extends AbstractCondition<TCondition>
 * @template-implements ValueDependentConditionInterface<TCondition>
 */
class NotIn extends AbstractCondition implements ValueDependentConditionInterface
{
    public const OPERATOR = 'NOT_IN';

    public function getOperator(): string
    {
        return self::OPERATOR;
    }

    public function transform(?array $path, mixed $value): PathsBasedInterface
    {
        Assert::notNull($path);
        Assert::isNonEmptyList($value);

        return $this->conditionFactory->propertyHasNotAnyOfValues($value, $path);
    }
}
