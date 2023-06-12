<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use Webmozart\Assert\Assert;
use function array_key_exists;

/**
 * @template TCondition of PathsBasedInterface
 * @template-implements DrupalConditionFactoryInterface<TCondition>
 *
 * @phpstan-import-type DrupalValue from DrupalConditionFactoryInterface
 */
class PredefinedDrupalConditionFactory implements DrupalConditionFactoryInterface
{
    /**
     * @var array<non-empty-string, callable(DrupalValue, non-empty-list<non-empty-string>):TCondition>
     */
    private array $operatorFunctions;

    /**
     * @param PathsBasedConditionFactoryInterface<TCondition> $conditionFactory
     */
    public function __construct(
        protected readonly PathsBasedConditionFactoryInterface $conditionFactory
    ) {
        $this->operatorFunctions = $this->getOperatorFunctions();
    }

    public function getSupportedOperators(): array
    {
        return array_keys($this->operatorFunctions);
    }

    public function createCondition(string $operatorName, array|string|int|float|bool|null $value, array $path): PathsBasedInterface
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
            'BETWEEN' => function ($conditionValue, array $path): PathsBasedInterface {
                Assert::isArray($conditionValue);
                Assert::keyExists($conditionValue, 0);
                Assert::keyExists($conditionValue, 1);
                Assert::numeric($conditionValue[0]);
                Assert::numeric($conditionValue[1]);

                return $this->conditionFactory->propertyBetweenValuesInclusive($conditionValue[0], $conditionValue[1], $path);
            },
            'NOT BETWEEN' => function ($conditionValue, array $path): PathsBasedInterface {
                Assert::isArray($conditionValue);
                Assert::keyExists($conditionValue, 0);
                Assert::keyExists($conditionValue, 1);
                Assert::numeric($conditionValue[0]);
                Assert::numeric($conditionValue[1]);

                return $this->conditionFactory->propertyNotBetweenValuesInclusive($conditionValue[0], $conditionValue[1], $path);
            },
            'ARRAY_CONTAINS_VALUE' => [$this->conditionFactory, 'propertyHasStringAsMember'],
            'IS NULL' => fn ($conditionValue, array $path): PathsBasedInterface => $this->conditionFactory->propertyIsNull($path),
            'IS NOT NULL' => fn ($conditionValue, array $path): PathsBasedInterface => $this->conditionFactory->propertyIsNotNull($path),
            '>' => [$this->conditionFactory, 'valueGreaterThan'],
            '>=' => [$this->conditionFactory, 'valueGreaterEqualsThan'],
            '<' => [$this->conditionFactory, 'valueSmallerThan'],
            '<=' => [$this->conditionFactory, 'valueSmallerEqualsThan'],
            'STARTS_WITH_CASE_INSENSITIVE' => [$this->conditionFactory, 'propertyStartsWithCaseInsensitive'],
            'ENDS_WITH_CASE_INSENSITIVE' => [$this->conditionFactory, 'propertyEndsWithCaseInsensitive'],
        ];
    }
}
