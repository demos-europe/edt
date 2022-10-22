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
    private PathsBasedConditionFactoryInterface $conditionFactory;

    /**
     * @var array<non-empty-string, callable(non-empty-list<non-empty-string>, mixed):TCondition>
     */
    private array $operatorFunctions;

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
            '=' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyHasValue($conditionValue, ...$path),
            '<>' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyHasNotValue($conditionValue, ...$path),
            'STRING_CONTAINS_CASE_INSENSITIVE' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue($conditionValue, ...$path),
            'IN' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyHasAnyOfValues($conditionValue, ...$path),
            'NOT_IN' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyHasNotAnyOfValues($conditionValue, ...$path),
            'BETWEEN' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyBetweenValuesInclusive($conditionValue[0], $conditionValue[1], ...$path),
            'NOT BETWEEN' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyNotBetweenValuesInclusive($conditionValue[0], $conditionValue[1], ...$path),
            'ARRAY_CONTAINS_VALUE' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyHasStringAsMember($conditionValue, ...$path),
            'IS NULL' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyIsNull(...$path),
            'IS NOT NULL' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyIsNotNull(...$path),
            '>' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->valueGreaterThan($conditionValue, ...$path),
            '>=' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->valueGreaterEqualsThan($conditionValue, ...$path),
            '<' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->valueSmallerThan($conditionValue, ...$path),
            '<=' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->valueSmallerEqualsThan($conditionValue, ...$path),
            'STARTS_WITH_CASE_INSENSITIVE' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyStartsWithCaseInsensitive($conditionValue, ...$path),
            'ENDS_WITH_CASE_INSENSITIVE' => fn (array $path, $conditionValue): PathsBasedInterface => $this->conditionFactory->propertyEndsWithCaseInsensitive($conditionValue, ...$path),
        ];
    }
}
