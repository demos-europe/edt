<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\Conditions\AlwaysFalse;
use EDT\Querying\Conditions\AlwaysTrue;
use EDT\Querying\Conditions\Between;
use EDT\Querying\Conditions\ContainsAll;
use EDT\Querying\Conditions\EndsWith;
use EDT\Querying\Conditions\Equals;
use EDT\Querying\Conditions\GreaterEqualsThan;
use EDT\Querying\Conditions\GreaterThan;
use EDT\Querying\Conditions\In;
use EDT\Querying\Conditions\IsNotNull;
use EDT\Querying\Conditions\IsNull;
use EDT\Querying\Conditions\LesserEqualsThan;
use EDT\Querying\Conditions\LesserThan;
use EDT\Querying\Conditions\MemberOf;
use EDT\Querying\Conditions\NotBetween;
use EDT\Querying\Conditions\NotEquals;
use EDT\Querying\Conditions\NotIn;
use EDT\Querying\Conditions\NotMemberOf;
use EDT\Querying\Conditions\NotSize;
use EDT\Querying\Conditions\PropertiesEqual;
use EDT\Querying\Conditions\Size;
use EDT\Querying\Conditions\StartsWith;
use EDT\Querying\Conditions\StringContains;
use EDT\Querying\Contracts\PropertyPathInterface;

/**
 * @template-implements ConditionFactoryInterface<DrupalFilterInterface>
 * @template-implements ConditionGroupFactoryInterface<DrupalFilterInterface>
 */
class ConditionFactory implements ConditionGroupFactoryInterface, ConditionFactoryInterface
{
    public function propertyIsNull(array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithoutValue($properties, IsNull::OPERATOR);
    }

    public function propertyHasAnyOfValues(array $values, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, In::OPERATOR, $values);
    }

    public function propertyHasNotAnyOfValues(array $values, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, NotIn::OPERATOR, $values);
    }

    public function true()
    {
        return new MutableDrupalCondition(DrupalFilterParser::CONDITION, [
            DrupalFilterParser::OPERATOR => AlwaysTrue::OPERATOR,
        ]);
    }

    public function propertyHasSize(int $size, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, Size::OPERATOR, $size);
    }

    public function propertyHasNotSize(int $size, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, NotSize::OPERATOR, $size);
    }

    public function false()
    {
        return new MutableDrupalCondition(DrupalFilterParser::CONDITION, [
            DrupalFilterParser::OPERATOR => AlwaysFalse::OPERATOR,
        ]);
    }

    public function propertiesEqual(array $leftProperties, array $rightProperties)
    {
        // TODO (#150): support different context for right property path?
        return MutableDrupalCondition::createWithValue(
            $leftProperties,
            PropertiesEqual::OPERATOR,
            $leftProperties,
        );
    }

    public function propertyHasStringContainingCaseInsensitiveValue(string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, StringContains::OPERATOR, $value);
    }

    public function propertyHasValue(float|bool|int|string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, Equals::OPERATOR, $value);
    }

    public function propertyBetweenValuesInclusive(float|int|string $min, float|int|string $max, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, Between::OPERATOR, [$min, $max]);
    }

    public function propertyNotBetweenValuesInclusive(float|int|string $min, float|int|string $max, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, NotBetween::OPERATOR, [$min, $max]);
    }

    public function propertyHasStringAsMember(string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, MemberOf::OPERATOR, $value);
    }

    public function valueGreaterThan(float|int|string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, GreaterThan::OPERATOR, $value);
    }

    public function valueGreaterEqualsThan(float|int|string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, GreaterEqualsThan::OPERATOR, $value);
    }

    public function valueSmallerThan(float|int|string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, LesserThan::OPERATOR, $value);
    }

    public function valueSmallerEqualsThan(float|int|string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, LesserEqualsThan::OPERATOR, $value);
    }

    public function propertyStartsWithCaseInsensitive(string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, StartsWith::OPERATOR, $value);
    }

    public function propertyEndsWithCaseInsensitive(string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, EndsWith::OPERATOR, $value);
    }

    public function allValuesPresentInMemberListProperties(array $values, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, ContainsAll::OPERATOR, $values);
    }

    public function propertyHasNotValue(float|bool|int|string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, NotEquals::OPERATOR, $value);
    }

    public function propertyIsNotNull(array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithoutValue($properties, IsNotNull::OPERATOR);
    }

    public function propertyHasNotStringAsMember(string $value, array|string|PropertyPathInterface $properties)
    {
        return MutableDrupalCondition::createWithValue($properties, NotMemberOf::OPERATOR, $value);
    }

    public function allConditionsApply($firstCondition, ...$additionalConditions)
    {
        $group = MutableDrupalGroup::createAnd();
        $this->fillGroup($group, array_values([$firstCondition, ...$additionalConditions]));

        return $group;
    }

    public function anyConditionApplies($firstCondition, ...$additionalConditions)
    {
        $group = MutableDrupalGroup::createOr();
        $this->fillGroup($group, array_values([$firstCondition, ...$additionalConditions]));

        return $group;
    }

    /**
     * @param non-empty-list<DrupalFilterInterface> $conditions
     */
    protected function fillGroup(MutableDrupalGroup $group, array $conditions): void
    {
        foreach ($conditions as $condition) {
            $group->addChild($condition, '');
        }
    }
}
