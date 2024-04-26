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
class ArrayContains extends AbstractCondition implements ValueDependentConditionInterface
{
    public const OPERATOR = 'ARRAY_CONTAINS_VALUE';

    public function getOperator(): string
    {
        return self::OPERATOR;
    }

    public function transform(?array $path, mixed $value): PathsBasedInterface
    {
        Assert::notNull($path);
        Assert::string($value);

        return $this->conditionFactory->propertyHasStringAsMember($value, $path);
    }
}
