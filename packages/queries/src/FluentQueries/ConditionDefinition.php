<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\ConditionFactory\PathsBasedConditionGroupFactoryInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use function count;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
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
     * @var PathsBasedConditionFactoryInterface<TCondition>&PathsBasedConditionGroupFactoryInterface<TCondition>
     */
    protected object $conditionFactory;

    protected bool $andConjunction;

    /**
     * @param PathsBasedConditionFactoryInterface<TCondition>&PathsBasedConditionGroupFactoryInterface<TCondition> $conditionFactory
     */
    public function __construct(PathsBasedConditionFactoryInterface $conditionFactory, bool $andConjunction)
    {
        $this->conditionFactory = $conditionFactory;
        $this->andConjunction = $andConjunction;
    }

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
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyIsNull(string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyIsNull($property, ...$properties));
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
     * @param non-empty-string  $property
     * @param non-empty-string  ...$properties
     *
     * @return $this
     */
    public function propertyHasAnyOfValues(array $values, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyHasAnyOfValues($values, $property, ...$properties));
    }

    /**
     * @param list<mixed> $values
     * @param non-empty-string  $property
     * @param non-empty-string  ...$properties
     *
     * @return $this
     */
    public function propertyHasNotAnyOfValues(array $values, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyHasNotAnyOfValues($values, $property, ...$properties));
    }

    /**
     * @return $this
     */
    public function true(): self
    {
        return $this->add($this->conditionFactory->true());
    }

    /**
     * @param non-empty-string  $property
     * @param non-empty-string  ...$properties
     *
     * @return $this
     */
    public function propertyHasSize(int $size, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyHasSize($size, $property, ...$properties));
    }

    /**
     * @param non-empty-string  $property
     * @param non-empty-string  ...$properties
     *
     * @return $this
     */
    public function propertyHasNotSize(int $size, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyHasNotSize($size, $property, ...$properties));
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
     * @param non-empty-string  $property
     * @param non-empty-string  ...$properties
     *
     * @return $this
     */
    public function propertyHasStringContainingCaseInsensitiveValue(string $value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue($value, $property, ...$properties));
    }

    /**
     * @param mixed $value
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyHasValue($value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyHasValue($value, $property, ...$properties));
    }

    /**
     * @param mixed $min
     * @param mixed $max
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyBetweenValuesInclusive($min, $max, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyBetweenValuesInclusive($min, $max, $property, ...$properties));
    }

    /**
     * @param mixed $min
     * @param mixed $max
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyNotBetweenValuesInclusive($min, $max, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyNotBetweenValuesInclusive($min, $max, $property, ...$properties));
    }

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyHasStringAsMember(string $value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyHasStringAsMember($value, $property, ...$properties));
    }

    /**
     * @param mixed $value
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function valueGreaterThan($value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->valueGreaterThan($value, $property, ...$properties));
    }

    /**
     * @param mixed $value
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function valueGreaterEqualsThan($value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->valueGreaterEqualsThan($value, $property, ...$properties));
    }

    /**
     * @param mixed $value
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function valueSmallerThan($value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->valueSmallerThan($value, $property, ...$properties));
    }

    /**
     * @param mixed $value
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function valueSmallerEqualsThan($value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->valueSmallerEqualsThan($value, $property, ...$properties));
    }

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyStartsWithCaseInsensitive(string $value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyStartsWithCaseInsensitive($value, $property, ...$properties));
    }

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyEndsWithCaseInsensitive(string $value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyEndsWithCaseInsensitive($value, $property, ...$properties));
    }

    /**
     * @param non-empty-list<mixed>           $values
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function allValuesPresentInMemberListProperties(array $values, array $properties): self
    {
        return $this->add($this->conditionFactory->allValuesPresentInMemberListProperties($values, $properties));
    }

    /**
     * @param mixed            $value
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyHasNotValue($value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyHasNotValue($value, $property, ...$properties));
    }

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyIsNotNull(string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyIsNotNull($property, ...$properties));
    }

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyHasNotStringAsMember(string $value, string $property, string ...$properties): self
    {
        return $this->add($this->conditionFactory->propertyHasNotStringAsMember($value, $property, ...$properties));
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
        $conditions = $this->conditions;
        foreach ($this->subDefinitions as $definition) {
            $subConditions = $definition->getConditions();
            if ($definition->andConjunction === $this->andConjunction
                || 1 >= count($subConditions)) {
                $conditions = array_merge($conditions, $subConditions);
            } else {
                $conditions[] = $definition->andConjunction
                    ? $this->conditionFactory->allConditionsApply(...$subConditions)
                    : $this->conditionFactory->anyConditionApplies(...$subConditions);
            }
        }

        return $conditions;
    }
}
