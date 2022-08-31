<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use function array_key_exists;
use function is_array;
use function gettype;
use function is_string;

/**
 * Separates the conditions from the group definitions and builds two
 * indices from the group definitions. One for the conjunctions and one for the
 * memberOf value.
 */
class DrupalFilterObject
{
    /**
     * This group/condition key is reserved and can not be used in a request.
     *
     * The value is not specified by Drupal's JSON:API filter documentation. However,
     * it is used by Drupal's implementation and was thus adopted here and preferred over
     * alternatives like 'root' or '' (empty string).
     */
    public const ROOT_KEY = '@root';
    public const AND = 'AND';
    public const OR = 'OR';
    /**
     * The key of the field determining which filter group a condition or a subgroup is a member
     * of.
     */
    private const MEMBER_OF_KEY = 'memberOf';
    private const CONJUNCTION = 'conjunction';
    /**
     * The key identifying a field as data for a filter group.
     */
    private const GROUP_KEY = 'group';
    /**
     * The key identifying a field as data for a filter condition.
     */
    private const CONDITION_KEY = 'condition';
    /**
     * @var array<string,array<int,array{operator?: string, memberOf?: string, value?: mixed, path: string}>>
     */
    private $groupedConditions = [];
    /**
     * @var array<string,string>
     */
    private $groupNameToConjunction = [];
    /**
     * @var array<string,string>
     */
    private $groupNameToMemberOf = [];

    /**
     * This constructor receives the group definitions and conditions as a "flat" array, meaning all
     * group definitions and all conditions are on the first level, each having
     * a unique name.
     *
     * @param array<string,array{condition: array{operator?: string, memberOf?: string, value?: mixed, path: string}}|array{group: array{memberOf?: string, conjunction: string}}> $groupsAndConditions
     * @throws DrupalFilterException
     */
    public function __construct(array $groupsAndConditions)
    {
        // One special name is reserved for internal usage by the drupal filter specification.
        if (array_key_exists(self::ROOT_KEY, $groupsAndConditions)) {
            throw DrupalFilterException::rootKeyUsed();
        }

        foreach ($groupsAndConditions as $key => $groupOrCondition) {
            if (array_key_exists(self::GROUP_KEY, $groupOrCondition)) {
                // If an item defines a group its structure will be simplified,
                // and it is added to the groups with its unique name as key.
                $group = $this->getGroup($groupOrCondition);
                $this->groupNameToConjunction[$key] = $group[self::CONJUNCTION];
                $this->groupNameToMemberOf[$key] = $this->determineMemberOf($group);
            } elseif (array_key_exists(self::CONDITION_KEY, $groupOrCondition)) {
                // If an item is a condition then a condition object will be created from it.
                // That object is then added not directly to the conditions but to a bucket
                // instead. Each bucket contains all conditions that belong into the same
                // group. The buckets are added to the $conditions array with the unique
                // name of the corresponding group as key.
                $conditionArray = $groupOrCondition[self::CONDITION_KEY];
                if (!is_array($conditionArray)) {
                    throw DrupalFilterException::conditionNonArray($conditionArray);
                }
                $memberOf = $this->determineMemberOf($conditionArray);
                $this->groupedConditions[$memberOf][] = $conditionArray;
            } else {
                throw DrupalFilterException::neitherConditionNorGroup();
            }
        }
    }

    /**
     * @return array<string,array<int,array{operator?: string, memberOf?: string, value?: mixed, path: string}>>
     */
    public function getGroupedConditions(): array
    {
        return $this->groupedConditions;
    }

    /**
     * @return array<string,string>
     */
    public function getGroupNameToConjunction(): array
    {
        return $this->groupNameToConjunction;
    }

    /**
     * @return array<string,string>
     */
    public function getGroupNameToMemberOf(): array
    {
        return $this->groupNameToMemberOf;
    }

    /**
     * Get the unique name of the parent group of the given group or condition.
     *
     * @param array{memberOf?: string} $groupOrCondition
     * @throws DrupalFilterException
     */
    protected function determineMemberOf(array $groupOrCondition): string
    {
        if (array_key_exists(self::MEMBER_OF_KEY, $groupOrCondition)) {
            $memberOf = $groupOrCondition[self::MEMBER_OF_KEY];
            if (!is_string($memberOf)) {
                throw DrupalFilterException::memberOfType(gettype($memberOf));
            }
            if (self::ROOT_KEY === $memberOf) {
                throw DrupalFilterException::memberOfRoot();
            }

            return $memberOf;
        }

        return self::ROOT_KEY;
    }

    /**
     * @param array<string,array<string,mixed>> $groupWrapper
     * @return array<string,mixed>
     * @throws DrupalFilterException
     */
    private function getGroup(array $groupWrapper): array
    {
        $group = $groupWrapper[self::GROUP_KEY];
        if (!is_array($group)) {
            throw DrupalFilterException::groupNonArray($group);
        }
        foreach ($group as $key => $value) {
            if (self::CONJUNCTION !== $key && self::MEMBER_OF_KEY !== $key) {
                throw DrupalFilterException::unknownGroupField($key);
            }
        }
        if (!array_key_exists(self::CONJUNCTION, $group)) {
            throw DrupalFilterException::noConjunction();
        }
        $conjunctionString = $group[self::CONJUNCTION];
        if (!is_string($conjunctionString)) {
            throw DrupalFilterException::conjunctionType(gettype($conjunctionString));
        }
        switch ($conjunctionString) {
            case self::AND:
            case self::OR:
                return $group;
            default:
                throw DrupalFilterException::conjunctionUnavailable($conjunctionString);
        }
    }
}
