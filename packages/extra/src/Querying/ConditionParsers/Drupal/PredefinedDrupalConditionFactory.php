<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\Querying\Conditions\ArrayContains;
use EDT\Querying\Conditions\Between;
use EDT\Querying\Conditions\EndsWith;
use EDT\Querying\Conditions\Equals;
use EDT\Querying\Conditions\GreaterEqualsThan;
use EDT\Querying\Conditions\GreaterThan;
use EDT\Querying\Conditions\In;
use EDT\Querying\Conditions\IsNotNull;
use EDT\Querying\Conditions\IsNull;
use EDT\Querying\Conditions\LesserEqualsThan;
use EDT\Querying\Conditions\LesserThan;
use EDT\Querying\Conditions\NotBetween;
use EDT\Querying\Conditions\NotEquals;
use EDT\Querying\Conditions\NotIn;
use EDT\Querying\Conditions\NotSize;
use EDT\Querying\Conditions\Size;
use EDT\Querying\Conditions\StartsWith;
use EDT\Querying\Conditions\StringContains;
use EDT\Querying\Drupal\ConditionValueException;
use Webmozart\Assert\Assert;
use function array_key_exists;
use function is_bool;
use function is_string;

/**
 * @template TCondition
 * @template-implements DrupalConditionFactoryInterface<TCondition>
 *
 * @phpstan-import-type DrupalValue from DrupalFilterParser
 */
class PredefinedDrupalConditionFactory implements DrupalConditionFactoryInterface
{
    /**
     * @var array<non-empty-string, callable(DrupalValue, non-empty-list<non-empty-string>|null):TCondition>
     */
    private array $operatorFunctionsWithValue;

    /**
     * @var array<non-empty-string, callable(non-empty-list<non-empty-string>|null):TCondition>
     */
    private array $operatorFunctionsWithoutValue;

    /**
     * @param ConditionFactoryInterface<TCondition> $conditionFactory
     */
    public function __construct(
        protected readonly ConditionFactoryInterface $conditionFactory
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

    public function createConditionWithValue(string $operatorName, array|string|int|float|bool|null $value, ?array $path)
    {
        if (!array_key_exists($operatorName, $this->operatorFunctionsWithValue)) {
            throw DrupalFilterException::unknownCondition($operatorName, ...array_keys($this->getSupportedOperators()));
        }

        return $this->operatorFunctionsWithValue[$operatorName]($value, $path);
    }

    public function createConditionWithoutValue(string $operatorName, ?array $path)
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
     * @return int<0,max>
     */
    protected function assertNonNegativeInt(mixed $value): int
    {
        Assert::integer($value);
        Assert::greaterThanEq($value, 0);

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
     * @return non-empty-list<mixed>
     */
    protected function assertNonEmptyList(mixed $value): array
    {
        Assert::isNonEmptyList($value);

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
     * @return array<non-empty-string, callable(DrupalValue, non-empty-list<non-empty-string>|null): TCondition>
     */
    protected function getOperatorFunctionsWithValue(): array
    {
        return [
            Equals::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyHasValue($this->assertPrimitiveNonNull($value), $this->assertPath($path)),
            NotEquals::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyHasNotValue($this->assertPrimitiveNonNull($value), $this->assertPath($path)),
            StringContains::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue($this->assertString($value), $this->assertPath($path)),
            In::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyHasAnyOfValues($this->assertNonEmptyList($value), $this->assertPath($path)),
            NotIn::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyHasNotAnyOfValues($this->assertNonEmptyList($value), $this->assertPath($path)),
            Between::OPERATOR => function ($value, ?array $path) {
                $value = $this->assertRange($value);
                return $this->conditionFactory->propertyBetweenValuesInclusive($value[0], $value[1], $this->assertPath($path));
            },
            NotBetween::OPERATOR => function ($value, ?array $path) {
                $value = $this->assertRange($value);
                return $this->conditionFactory->propertyNotBetweenValuesInclusive($value[0], $value[1], $this->assertPath($path));
            },
            ArrayContains::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyHasStringAsMember($this->assertString($value), $this->assertPath($path)),
            GreaterThan::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->valueGreaterThan($this->assertNumeric($value), $this->assertPath($path)),
            GreaterEqualsThan::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->valueGreaterEqualsThan($this->assertNumeric($value), $this->assertPath($path)),
            LesserThan::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->valueSmallerThan($this->assertNumeric($value), $this->assertPath($path)),
            LesserEqualsThan::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->valueSmallerEqualsThan($this->assertNumeric($value), $this->assertPath($path)),
            StartsWith::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyStartsWithCaseInsensitive($this->assertString($value), $this->assertPath($path)),
            EndsWith::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyEndsWithCaseInsensitive($this->assertString($value), $this->assertPath($path)),
            NotSize::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyHasNotSize($this->assertNonNegativeInt($value), $this->assertPath($path)),
            Size::OPERATOR => fn ($value, ?array $path) => $this->conditionFactory->propertyHasSize($this->assertNonNegativeInt($value), $this->assertPath($path)),
        ];
    }

    /**
     * @return array<non-empty-string, callable(non-empty-list<non-empty-string>|null): TCondition>
     */
    protected function getOperatorFunctionsWithoutValue(): array
    {
        return [
            IsNull::OPERATOR => fn (?array $path) => $this->conditionFactory->propertyIsNull($this->assertPath($path)),
            IsNotNull::OPERATOR => fn (?array $path) => $this->conditionFactory->propertyIsNotNull($this->assertPath($path)),
        ];
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    protected function assertPath(?array $path): array
    {
        Assert::notNull($path);
        Assert::isList($path);
        Assert::notEmpty($path);
        Assert::allStringNotEmpty($path);

        return $path;
    }
}
