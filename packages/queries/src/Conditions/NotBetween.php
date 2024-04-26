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
class NotBetween extends AbstractCondition implements ValueDependentConditionInterface
{
    public const OPERATOR = 'NOT BETWEEN';

    public function getOperator(): string
    {
        return self::OPERATOR;
    }

    public function transform(?array $path, mixed $value): PathsBasedInterface
    {
        Assert::notNull($path);
        Assert::isList($value);
        Assert::allNumeric($value);
        Assert::count($value, 2);
        [$min, $max] = $value;

        return $this->conditionFactory->propertyNotBetweenValuesInclusive($min, $max, $path);
    }
}
