<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Drupal\ConditionValueException;
use EDT\Querying\Drupal\StandardOperator;
use Webmozart\Assert\Assert;
use function array_key_exists;
use function is_bool;
use function is_string;

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
    private array $operatorFunctionsWithValue;

    /**
     * @var array<non-empty-string, callable(non-empty-list<non-empty-string>):TCondition>
     */
    private array $operatorFunctionsWithoutValue;

    /**
     * @param PathsBasedConditionFactoryInterface<TCondition> $conditionFactory
     */
    public function __construct(
        protected readonly PathsBasedConditionFactoryInterface $conditionFactory
    ) {
        $this->operatorFunctionsWithValue = $this->getOperatorFunctionsWithValue();
        $this->operatorFunctionsWithoutValue = $this->getOperatorFunctionsWithoutValue();
    }

    public function getSupportedOperators(): array
    {
        $operators = array_merge($this->operatorFunctionsWithValue, $this->operatorFunctionsWithoutValue);

        return array_map(
            // FIXME: add operator specific conditions and validate condition-specific values and their type
            fn (callable $callable): array => [],
            $operators
        );
    }

    public function createConditionWithValue(string $operatorName, array|string|int|float|bool|null $value, array $path): PathsBasedInterface
    {
        if (!array_key_exists($operatorName, $this->operatorFunctionsWithValue)) {
            throw DrupalFilterException::unknownCondition($operatorName, ...array_keys($this->getSupportedOperators()));
        }

        return $this->operatorFunctionsWithValue[$operatorName]($value, $path);
    }

    public function createConditionWithoutValue(string $operatorName, array $path): PathsBasedInterface
    {
        if (!array_key_exists($operatorName, $this->operatorFunctionsWithoutValue)) {
            throw DrupalFilterException::unknownCondition($operatorName, ...array_keys($this->getSupportedOperators()));
        }

        return $this->operatorFunctionsWithoutValue[$operatorName]($path);
    }

    /**
     * @throws ConditionValueException
     */
    protected function assertPrimitiveNonNull(mixed $value): string|int|float|bool
    {
        if (!is_string($value)
            && !is_numeric($value)
            && !is_bool($value)) {
            $type = gettype($value);
            throw new ConditionValueException("Invalid value type `$type` provided.");
        }

        return $value;
    }

    protected function assertString(mixed $value): string
    {
        Assert::string($value);

        return $value;
    }

    /**
     * @return list<mixed>
     */
    protected function assertList(mixed $value): array
    {
        Assert::isList($value);

        return $value;
    }

    /**
     * @return array{numeric-string|int|float, numeric-string|int|float}
     */
    protected function assertRange(mixed $value): array
    {
        Assert::isArray($value);
        Assert::keyExists($value, 0);
        Assert::keyExists($value, 1);
        Assert::numeric($value[0]);
        Assert::numeric($value[1]);

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return numeric-string|int|float
     */
    protected function assertNumeric(mixed $value): string|int|float
    {
        Assert::numeric($value);

        return $value;
    }

    /**
     * @return array<non-empty-string, callable(DrupalValue, non-empty-list<non-empty-string>): TCondition>
     */
    protected function getOperatorFunctionsWithValue(): array
    {
        return [
            StandardOperator::EQUALS => fn ($value, array $path) => $this->conditionFactory->propertyHasValue($this->assertPrimitiveNonNull($value), $this->assertPath($path)),
            StandardOperator::NOT_EQUALS => fn ($value, array $path) => $this->conditionFactory->propertyHasNotValue($this->assertPrimitiveNonNull($value), $this->assertPath($path)),
            StandardOperator::STRING_CONTAINS_CASE_INSENSITIVE => fn ($value, array $path) => $this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue($this->assertString($value), $this->assertPath($path)),
            StandardOperator::IN => fn ($value, array $path) => $this->conditionFactory->propertyHasAnyOfValues($this->assertList($value), $this->assertPath($path)),
            StandardOperator::NOT_IN => fn ($value, array $path) => $this->conditionFactory->propertyHasNotAnyOfValues($this->assertList($value), $this->assertPath($path)),
            StandardOperator::BETWEEN => function ($value, array $path): PathsBasedInterface {
                $value = $this->assertRange($value);
                return $this->conditionFactory->propertyBetweenValuesInclusive($value[0], $value[1], $this->assertPath($path));
            },
            StandardOperator::NOT_BETWEEN => function ($value, array $path): PathsBasedInterface {
                $value = $this->assertRange($value);
                return $this->conditionFactory->propertyNotBetweenValuesInclusive($value[0], $value[1], $this->assertPath($path));
            },
            StandardOperator::ARRAY_CONTAINS_VALUE => fn ($value, array $path) => $this->conditionFactory->propertyHasStringAsMember($this->assertString($value), $this->assertPath($path)),
            StandardOperator::GT => fn ($value, array $path) => $this->conditionFactory->valueGreaterThan($this->assertNumeric($value), $this->assertPath($path)),
            StandardOperator::GTEQ => fn ($value, array $path) => $this->conditionFactory->valueGreaterEqualsThan($this->assertNumeric($value), $this->assertPath($path)),
            StandardOperator::LT => fn ($value, array $path) => $this->conditionFactory->valueSmallerThan($this->assertNumeric($value), $this->assertPath($path)),
            StandardOperator::LTEQ => fn ($value, array $path) => $this->conditionFactory->valueSmallerEqualsThan($this->assertNumeric($value), $this->assertPath($path)),
            StandardOperator::STARTS_WITH_CASE_INSENSITIVE => fn ($value, array $path) => $this->conditionFactory->propertyStartsWithCaseInsensitive($this->assertString($value), $this->assertPath($path)),
            StandardOperator::ENDS_WITH_CASE_INSENSITIVE => fn ($value, array $path) => $this->conditionFactory->propertyEndsWithCaseInsensitive($this->assertString($value), $this->assertPath($path)),
        ];
    }

    /**
     * @return array<non-empty-string, callable(non-empty-list<non-empty-string>): TCondition>
     */
    protected function getOperatorFunctionsWithoutValue(): array
    {
        return [
            StandardOperator::IS_NULL => fn (array $path): PathsBasedInterface => $this->conditionFactory->propertyIsNull($path),
            StandardOperator::IS_NOT_NULL => fn (array $path): PathsBasedInterface => $this->conditionFactory->propertyIsNotNull($path),
        ];
    }

    /**
     * @param array<int|string, mixed> $path
     *
     * @return non-empty-list<non-empty-string>
     */
    protected function assertPath(array $path): array
    {
        Assert::isList($path);
        Assert::notEmpty($path);
        Assert::allStringNotEmpty($path);

        return $path;
    }
}
