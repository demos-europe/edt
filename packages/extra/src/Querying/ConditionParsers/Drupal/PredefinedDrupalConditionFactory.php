<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Drupal\StandardOperator;
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
            StandardOperator::EQUALS => [$this->conditionFactory, 'propertyHasValue'],
            StandardOperator::NOT_EQUALS => [$this->conditionFactory, 'propertyHasNotValue'],
            StandardOperator::STRING_CONTAINS_CASE_INSENSITIVE => [$this->conditionFactory, 'propertyHasStringContainingCaseInsensitiveValue'],
            StandardOperator::IN => [$this->conditionFactory, 'propertyHasAnyOfValues'],
            StandardOperator::NOT_IN => [$this->conditionFactory, 'propertyHasNotAnyOfValues'],
            StandardOperator::BETWEEN => function ($conditionValue, array $path): PathsBasedInterface {
                Assert::isArray($conditionValue);
                Assert::keyExists($conditionValue, 0);
                Assert::keyExists($conditionValue, 1);
                Assert::numeric($conditionValue[0]);
                Assert::numeric($conditionValue[1]);

                return $this->conditionFactory->propertyBetweenValuesInclusive($conditionValue[0], $conditionValue[1], $path);
            },
            StandardOperator::NOT_BETWEEN => function ($conditionValue, array $path): PathsBasedInterface {
                Assert::isArray($conditionValue);
                Assert::keyExists($conditionValue, 0);
                Assert::keyExists($conditionValue, 1);
                Assert::numeric($conditionValue[0]);
                Assert::numeric($conditionValue[1]);

                return $this->conditionFactory->propertyNotBetweenValuesInclusive($conditionValue[0], $conditionValue[1], $path);
            },
            StandardOperator::ARRAY_CONTAINS_VALUE => [$this->conditionFactory, 'propertyHasStringAsMember'],
            StandardOperator::IS_NULL => fn ($conditionValue, array $path): PathsBasedInterface => $this->conditionFactory->propertyIsNull($path),
            StandardOperator::IS_NOT_NULL => fn ($conditionValue, array $path): PathsBasedInterface => $this->conditionFactory->propertyIsNotNull($path),
            StandardOperator::GT => [$this->conditionFactory, 'valueGreaterThan'],
            StandardOperator::GTEQ => [$this->conditionFactory, 'valueGreaterEqualsThan'],
            StandardOperator::LT => [$this->conditionFactory, 'valueSmallerThan'],
            StandardOperator::LTEQ => [$this->conditionFactory, 'valueSmallerEqualsThan'],
            StandardOperator::STARTS_WITH_CASE_INSENSITIVE => [$this->conditionFactory, 'propertyStartsWithCaseInsensitive'],
            StandardOperator::ENDS_WITH_CASE_INSENSITIVE => [$this->conditionFactory, 'propertyEndsWithCaseInsensitive'],
        ];
    }
}
