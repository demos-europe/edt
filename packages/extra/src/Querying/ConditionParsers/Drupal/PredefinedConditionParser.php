<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\FunctionInterface;
use function array_key_exists;

class PredefinedConditionParser extends DrupalConditionParser
{
    /**
     * @param mixed $conditionValue
     * @return array<string,callable():FunctionInterface<bool>>
     */
    protected function getConditionFactories($conditionValue, string ...$path): array
    {
        return [
            '=' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasValue($conditionValue, ...$path);
            },
            '<>' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasNotValue($conditionValue, ...$path);
            },
            'STRING_CONTAINS_CASE_INSENSITIVE' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue($conditionValue, ...$path);
            },
            'IN' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasAnyOfValues($conditionValue, ...$path);
            },
            'NOT_IN' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasNotAnyOfValues($conditionValue, ...$path);
            },
            'BETWEEN' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyBetweenValuesInclusive($conditionValue[0], $conditionValue[1], ...$path);
            },
            'NOT BETWEEN' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyNotBetweenValuesInclusive($conditionValue[0], $conditionValue[1], ...$path);
            },
            'ARRAY_CONTAINS_VALUE' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasStringAsMember($conditionValue, ...$path);
            },
            'IS NULL' => function () use ($path): FunctionInterface {
                return $this->conditionFactory->propertyIsNull(...$path);
            },
            'IS NOT NULL' => function () use ($path): FunctionInterface {
                return $this->conditionFactory->propertyIsNotNull(...$path);
            },
            '>' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->valueGreaterThan($conditionValue, ...$path);
            },
            '>=' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->valueGreaterEqualsThan($conditionValue, ...$path);
            },
            '<' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->valueSmallerThan($conditionValue, ...$path);
            },
            '<=' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->valueSmallerEqualsThan($conditionValue, ...$path);
            },
            'STARTS_WITH_CASE_INSENSITIVE' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyStartsWithCaseInsensitive($conditionValue, ...$path);
            },
            'ENDS_WITH_CASE_INSENSITIVE' => function () use ($path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyEndsWithCaseInsensitive($conditionValue, ...$path);
            },
        ];
    }

    /**
     * @param mixed $conditionValue
     * @return FunctionInterface<bool>
     * @throws DrupalFilterException
     */
    protected function createCondition(string $conditionName, $conditionValue, string $pathPart, string ...$pathParts): FunctionInterface
    {
        $factories = $this->getConditionFactories($conditionValue, $pathPart, ...$pathParts);
        if (array_key_exists($conditionName, $factories)) {
            return $factories[$conditionName]();
        }
        throw DrupalFilterException::unknownCondition($conditionName, ...array_keys($factories));
    }
}
