<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\FunctionInterface;
use function array_key_exists;

/**
 * @template F of FunctionInterface<bool>
 * @template-implements OperatorProviderInterface<F>
 */
class PredefinedOperatorProvider implements OperatorProviderInterface
{
    /**
     * @var ConditionFactoryInterface<F>
     */
    private $conditionFactory;

    /**
     * @var array<non-empty-string, callable(non-empty-list<non-empty-string>, mixed):F>
     */
    private $operatorFunctions;

    /**
     * @param ConditionFactoryInterface<F> $conditionFactory
     */
    public function __construct(ConditionFactoryInterface $conditionFactory)
    {
        $this->conditionFactory = $conditionFactory;
        $this->operatorFunctions = $this->getOperatorFunctions();
    }

    public function getAllOperatorNames(): array
    {
        return array_keys($this->operatorFunctions);
    }

    public function createOperator(string $operatorName, $value, array $path): FunctionInterface
    {
        if (!array_key_exists($operatorName, $this->operatorFunctions)) {
            throw DrupalFilterException::unknownCondition($operatorName, ...$this->getAllOperatorNames());
        }

        return $this->operatorFunctions[$operatorName]($path, $value);
    }

    /**
     * @return array<non-empty-string, callable(non-empty-list<non-empty-string>, mixed): F>
     */
    protected function getOperatorFunctions(): array
    {
        return [
            '=' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasValue($conditionValue, ...$path);
            },
            '<>' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasNotValue($conditionValue, ...$path);
            },
            'STRING_CONTAINS_CASE_INSENSITIVE' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue($conditionValue, ...$path);
            },
            'IN' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasAnyOfValues($conditionValue, ...$path);
            },
            'NOT_IN' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasNotAnyOfValues($conditionValue, ...$path);
            },
            'BETWEEN' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyBetweenValuesInclusive($conditionValue[0], $conditionValue[1], ...$path);
            },
            'NOT BETWEEN' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyNotBetweenValuesInclusive($conditionValue[0], $conditionValue[1], ...$path);
            },
            'ARRAY_CONTAINS_VALUE' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyHasStringAsMember($conditionValue, ...$path);
            },
            'IS NULL' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyIsNull(...$path);
            },
            'IS NOT NULL' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyIsNotNull(...$path);
            },
            '>' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->valueGreaterThan($conditionValue, ...$path);
            },
            '>=' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->valueGreaterEqualsThan($conditionValue, ...$path);
            },
            '<' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->valueSmallerThan($conditionValue, ...$path);
            },
            '<=' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->valueSmallerEqualsThan($conditionValue, ...$path);
            },
            'STARTS_WITH_CASE_INSENSITIVE' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyStartsWithCaseInsensitive($conditionValue, ...$path);
            },
            'ENDS_WITH_CASE_INSENSITIVE' => function (array $path, $conditionValue): FunctionInterface {
                return $this->conditionFactory->propertyEndsWithCaseInsensitive($conditionValue, ...$path);
            },
        ];
    }
}
