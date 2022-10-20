<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use function array_key_exists;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template-implements DrupalConditionFactoryInterface<TCondition>
 */
class PredefinedDrupalConditionFactory implements DrupalConditionFactoryInterface
{
    /**
     * @var PathsBasedConditionFactoryInterface<TCondition>
     */
    private $conditionFactory;

    /**
     * @var array<non-empty-string, callable(non-empty-list<non-empty-string>, mixed):TCondition>
     */
    private $operatorFunctions;

    /**
     * @param PathsBasedConditionFactoryInterface<TCondition> $conditionFactory
     */
    public function __construct(PathsBasedConditionFactoryInterface $conditionFactory)
    {
        $this->conditionFactory = $conditionFactory;
        $this->operatorFunctions = $this->getOperatorFunctions();
    }

    public function getSupportedOperators(): array
    {
        return array_keys($this->operatorFunctions);
    }

    public function createCondition(string $operatorName, $value, array $path): PathsBasedInterface
    {
        if (!array_key_exists($operatorName, $this->operatorFunctions)) {
            throw DrupalFilterException::unknownCondition($operatorName, ...$this->getSupportedOperators());
        }

        return $this->operatorFunctions[$operatorName]($path, $value);
    }

    /**
     * @return array<non-empty-string, callable(non-empty-list<non-empty-string>, mixed): TCondition>
     */
    protected function getOperatorFunctions(): array
    {
        return [
            '=' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyHasValue($conditionValue, ...$path);
            },
            '<>' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyHasNotValue($conditionValue, ...$path);
            },
            'STRING_CONTAINS_CASE_INSENSITIVE' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue($conditionValue, ...$path);
            },
            'IN' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyHasAnyOfValues($conditionValue, ...$path);
            },
            'NOT_IN' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyHasNotAnyOfValues($conditionValue, ...$path);
            },
            'BETWEEN' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyBetweenValuesInclusive($conditionValue[0], $conditionValue[1], ...$path);
            },
            'NOT BETWEEN' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyNotBetweenValuesInclusive($conditionValue[0], $conditionValue[1], ...$path);
            },
            'ARRAY_CONTAINS_VALUE' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyHasStringAsMember($conditionValue, ...$path);
            },
            'IS NULL' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyIsNull(...$path);
            },
            'IS NOT NULL' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyIsNotNull(...$path);
            },
            '>' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->valueGreaterThan($conditionValue, ...$path);
            },
            '>=' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->valueGreaterEqualsThan($conditionValue, ...$path);
            },
            '<' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->valueSmallerThan($conditionValue, ...$path);
            },
            '<=' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->valueSmallerEqualsThan($conditionValue, ...$path);
            },
            'STARTS_WITH_CASE_INSENSITIVE' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyStartsWithCaseInsensitive($conditionValue, ...$path);
            },
            'ENDS_WITH_CASE_INSENSITIVE' => function (array $path, $conditionValue): PathsBasedInterface {
                return $this->conditionFactory->propertyEndsWithCaseInsensitive($conditionValue, ...$path);
            },
        ];
    }
}
