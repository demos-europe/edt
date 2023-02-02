<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\ConditionFactory\PathsBasedConditionGroupFactoryInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use function count;

/**
 * @template TCondition of PathsBasedInterface
 */
class ConditionDefinition
{
    /**
     * @var list<TCondition>
     */
    protected array $conditions = [];

    /**
     * @var list<ConditionDefinition<TCondition>>
     */
    protected array $subDefinitions = [];

    /**
     * @param PathsBasedConditionFactoryInterface<TCondition>&PathsBasedConditionGroupFactoryInterface<TCondition> $conditionFactory
     */
    public function __construct(
        protected PathsBasedConditionFactoryInterface $conditionFactory,
        protected bool $andConjunction
    ) {}

    /**
     * @return ConditionDefinition<TCondition>
     */
    public function anyConditionApplies(): ConditionDefinition
    {
        $subDefinition = new ConditionDefinition($this->conditionFactory, false);
        $this->subDefinitions[] = $subDefinition;
        return $subDefinition;
    }

    /**
     * @return ConditionDefinition<TCondition>
     */
    public function allConditionsApply(): ConditionDefinition
    {
        $subDefinition = new ConditionDefinition($this->conditionFactory, true);
        $this->subDefinitions[] = $subDefinition;
        return $subDefinition;
    }

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyIsNull(array $properties): self
    {
        return $this->add($this->conditionFactory->propertyIsNull($properties));
    }

    /**
     * @param TCondition $condition
     * @return $this
     */
    protected function add(PathsBasedInterface $condition): self
    {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * @param list<mixed> $values
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyHasAnyOfValues(array $values, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyHasAnyOfValues($values, $properties));
    }

    /**
     * @param list<mixed> $values
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyHasNotAnyOfValues(array $values, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyHasNotAnyOfValues($values, $properties));
    }

    /**
     * @return $this
     */
    public function true(): self
    {
        return $this->add($this->conditionFactory->true());
    }

    /**
     * @param int<0, max> $size
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyHasSize(int $size, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyHasSize($size, $properties));
    }

    /**
     * @param int<0, max> $size
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyHasNotSize(int $size, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyHasNotSize($size, $properties));
    }

    public function false(): self
    {
        return $this->add($this->conditionFactory->false());
    }

    /**
     * @param non-empty-list<non-empty-string> $leftProperties
     * @param non-empty-list<non-empty-string> $rightProperties
     *
     * @return $this
     */
    public function propertiesEqual(array $leftProperties, array $rightProperties): self
    {
        return $this->add($this->conditionFactory->propertiesEqual($leftProperties, $rightProperties));
    }

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyHasStringContainingCaseInsensitiveValue(string $value, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue($value, $properties));
    }

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyHasValue(string|int|float|bool $value, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyHasValue($value, $properties));
    }

    /**
     * @param numeric-string|int|float $min
     * @param numeric-string|int|float $max
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyBetweenValuesInclusive(string|int|float $min, string|int|float $max, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyBetweenValuesInclusive($min, $max, $properties));
    }

    /**
     * @param numeric-string|int|float $min
     * @param numeric-string|int|float $max
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyNotBetweenValuesInclusive(string|int|float $min, string|int|float $max, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyNotBetweenValuesInclusive($min, $max, $properties));
    }

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyHasStringAsMember(string $value, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyHasStringAsMember($value, $properties));
    }

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function valueGreaterThan(string|int|float $value, array $properties): self
    {
        return $this->add($this->conditionFactory->valueGreaterThan($value, $properties));
    }

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function valueGreaterEqualsThan(string|int|float $value, array $properties): self
    {
        return $this->add($this->conditionFactory->valueGreaterEqualsThan($value, $properties));
    }

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function valueSmallerThan(string|int|float $value, array $properties): self
    {
        return $this->add($this->conditionFactory->valueSmallerThan($value, $properties));
    }

    /**
     * @param numeric-string|int|float $value
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function valueSmallerEqualsThan(string|int|float $value, array $properties): self
    {
        return $this->add($this->conditionFactory->valueSmallerEqualsThan($value, $properties));
    }

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyStartsWithCaseInsensitive(string $value, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyStartsWithCaseInsensitive($value, $properties));
    }

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyEndsWithCaseInsensitive(string $value, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyEndsWithCaseInsensitive($value, $properties));
    }

    /**
     * @param non-empty-list<int|string|float|bool> $values
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function allValuesPresentInMemberListProperties(array $values, array $properties): self
    {
        return $this->add($this->conditionFactory->allValuesPresentInMemberListProperties($values, $properties));
    }

    /**
     * @param simple_primitive $value
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyHasNotValue(string|int|float|bool $value, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyHasNotValue($value, $properties));
    }

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyIsNotNull(array $properties): self
    {
        return $this->add($this->conditionFactory->propertyIsNotNull($properties));
    }

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyHasNotStringAsMember(string $value, array $properties): self
    {
        return $this->add($this->conditionFactory->propertyHasNotStringAsMember($value, $properties));
    }

    /**
     * @return list<TCondition>
     */
    public function getConditions(): array
    {
        return $this->processSubDefinitions();
    }

    /**
     * Iterates through all {@link ConditionDefinition::subDefinitions sub definitions} and first tries to flat merge the conditions
     * of each one with the {@link ConditionDefinition::conditions conditions} of this instance. If this is not possible, then a new
     * condition will be created from the sub definition by merging its conditions. The created
     * condition is merged with the {@link ConditionDefinition::conditions conditions} of this instance. The merge
     * result is returned.
     *
     * A {@link ConditionDefinition::subDefinitions sub definition} is considered to be mergeable if
     * it either uses the same {@link ConditionDefinition::$andConjunction conjunction} as its parent or
     * if it contains none or only one condition, in which case its conjunction doesn't matter.
     *
     * The process is recursive, meaning the same is done inside each item in {@link self::subDefinitions}.
     *
     * **No {@link ConditionDefinition::conditions} property of any {@link ConditionDefinition} instance will be modified
     * in the process.**
     *
     * @return list<TCondition>
     */
    protected function processSubDefinitions(): array
    {
        $nestedConditions = array_map(function (ConditionDefinition $definition): array {
            $subConditions = $definition->getConditions();
            if ($definition->andConjunction === $this->andConjunction || 1 >= count($subConditions)) {
                return $subConditions;
            }

            $conditionGroup = $definition->andConjunction
                ? $this->conditionFactory->allConditionsApply(...$subConditions)
                : $this->conditionFactory->anyConditionApplies(...$subConditions);

            return [$conditionGroup];
        }, $this->subDefinitions);

        return array_merge($this->conditions, ...$nestedConditions);
    }
}
