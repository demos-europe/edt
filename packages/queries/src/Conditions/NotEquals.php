<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use EDT\Querying\Contracts\PathsBasedInterface;
use Webmozart\Assert\Assert;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * @template TCondition of PathsBasedInterface
 *
 * @template-extends AbstractCondition<TCondition>
 * @template-implements ValueDependentConditionInterface<TCondition>
 */
class NotEquals extends AbstractCondition implements ValueDependentConditionInterface
{
    public const OPERATOR = '<>';

    public function getOperator(): string
    {
        return self::OPERATOR;
    }

    public function transform(?array $path, mixed $value): PathsBasedInterface
    {
        Assert::notNull($path);

        return is_bool($value) || is_int($value) || is_float($value) || is_string($value)
            ? $this->conditionFactory->propertyHasNotValue($value, $path)
            : throw new ConditionValueTypeException(['bool', 'int', 'float', 'string'], $value);
    }
}
