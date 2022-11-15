<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use function array_key_exists;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template-implements DrupalConditionFactoryInterface<TCondition>
 *
 * @phpstan-import-type DrupalValue from DrupalConditionFactoryInterface
 */
class PredefinedDrupalConditionFactory implements DrupalConditionFactoryInterface
{
    /**
     * @var PathsBasedConditionFactoryInterface<TCondition>
     */
    private PathsBasedConditionFactoryInterface $conditionFactory;

    /**
     * @var array<non-empty-string, callable(DrupalValue, non-empty-list<non-empty-string>):TCondition>
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

        return $this->operatorFunctions[$operatorName]($value, $path);
    }

    /**
     * @return array<non-empty-string, callable(DrupalValue, non-empty-list<non-empty-string>): TCondition>
     */
    protected function getOperatorFunctions(): array
    {
        // TODO: validate condition-specific value types
        return [
            '=' => [$this->conditionFactory, 'propertyHasValue'],
            '<>' => [$this->conditionFactory, 'propertyHasNotValue'],
            'STRING_CONTAINS_CASE_INSENSITIVE' => [$this->conditionFactory, 'propertyHasStringContainingCaseInsensitiveValue'],
            'IN' => [$this->conditionFactory, 'propertyHasAnyOfValues'],
            'NOT_IN' => [$this->conditionFactory, 'propertyHasNotAnyOfValues'],
            'BETWEEN' => fn ($conditionValue, array $path): PathsBasedInterface => $this->conditionFactory->propertyBetweenValuesInclusive($conditionValue[0], $conditionValue[1], $path),
            'NOT BETWEEN' => fn ($conditionValue, array $path): PathsBasedInterface => $this->conditionFactory->propertyNotBetweenValuesInclusive($conditionValue[0], $conditionValue[1], $path),
            'ARRAY_CONTAINS_VALUE' => [$this->conditionFactory, 'propertyHasStringAsMember'],
            'IS NULL' => [$this->conditionFactory, 'propertyIsNull'],
            'IS NOT NULL' => [$this->conditionFactory, 'propertyIsNotNull'],
            '>' => [$this->conditionFactory, 'valueGreaterThan'],
            '>=' => [$this->conditionFactory, 'valueGreaterEqualsThan'],
            '<' => [$this->conditionFactory, 'valueSmallerThan'],
            '<=' => [$this->conditionFactory, 'valueSmallerEqualsThan'],
            'STARTS_WITH_CASE_INSENSITIVE' => [$this->conditionFactory, 'propertyStartsWithCaseInsensitive'],
            'ENDS_WITH_CASE_INSENSITIVE' => [$this->conditionFactory, 'propertyEndsWithCaseInsensitive'],
        ];
    }
}
