<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use EDT\Querying\Contracts\PathsBasedInterface;
use Webmozart\Assert\Assert;
use function is_float;
use function is_int;

/**
 * @template TCondition of PathsBasedInterface
 *
 * @template-extends AbstractCondition<TCondition>
 * @template-implements ValueDependentConditionInterface<TCondition>
 */
class LesserThan extends AbstractCondition implements ValueDependentConditionInterface
{
    public const OPERATOR = '<';

    public function getOperator(): string
    {
        return self::OPERATOR;
    }

    public function transform(?array $path, mixed $value): PathsBasedInterface
    {
        Assert::notNull($path);

        return is_int($value) || is_float($value)
            ? $this->conditionFactory->valueSmallerThan($value, $path)
            : throw new ConditionValueTypeException(['int', 'float'], $value);
    }
}
