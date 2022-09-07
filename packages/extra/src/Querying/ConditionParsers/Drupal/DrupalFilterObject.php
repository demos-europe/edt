<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use function array_key_exists;

/**
 * Separates the conditions from the group definitions and builds two
 * indices from the group definitions. One for the conjunctions and one for the
 * memberOf value.
 *
 * @psalm-type DrupalFilterGroup = array{
 *            conjunction: DrupalFilterObject::AND|DrupalFilterObject::OR,
 *            memberOf?: string
 *          }
 * @psalm-type DrupalFilterCondition = array{
 *            path: string,
 *            value?: mixed,
 *            operator?: string,
 *            memberOf?: string
 *          }
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
    public const ROOT = '@root';
    /**
     * All conditions in the group must apply.
     */
    public const AND = 'AND';
    /**
     * Any condition in the group must apply.
     */
    public const OR = 'OR';
    /**
     * The key of the field determining which filter group a condition or a subgroup is a member
     * of.
     */
    private const MEMBER_OF = 'memberOf';
    /**
     * The key for the field in which "AND" or "OR" is stored.
     */
    private const CONJUNCTION = 'conjunction';
    /**
     * The key identifying a field as data for a filter group.
     */
    private const GROUP = 'group';
    /**
     * The key identifying a field as data for a filter condition.
     */
    private const CONDITION = 'condition';
    /**
     * @var array<string,array<int,DrupalFilterCondition>>
     */
    private $groupedConditions = [];
    /**
     * @var array<string, DrupalFilterObject::AND|DrupalFilterObject::OR>
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
     * @param array<string,array{condition: DrupalFilterCondition}|array{group: DrupalFilterGroup}> $groupsAndConditions
     * @throws DrupalFilterException
     */
    public function __construct(array $groupsAndConditions)
    {
        // One special name is reserved for internal usage by the drupal filter specification.
        if (array_key_exists(self::ROOT, $groupsAndConditions)) {
            throw DrupalFilterException::rootKeyUsed();
        }

        foreach ($groupsAndConditions as $filterName => $groupOrCondition) {
            if (array_key_exists(self::GROUP, $groupOrCondition)) {
                // If an item defines a group its structure will be simplified,
                // and it is added to the groups with its unique name as key.
                $group = $this->validateGroup($groupOrCondition[self::GROUP]);
                $this->groupNameToConjunction[$filterName] = $group[self::CONJUNCTION];
                $this->groupNameToMemberOf[$filterName] = $this->determineMemberOf($group);
            } elseif (array_key_exists(self::CONDITION, $groupOrCondition)) {
                // If an item is a condition then a condition object will be created from it.
                // That object is then added not directly to the conditions but to a bucket
                // instead. Each bucket contains all conditions that belong into the same
                // group. The buckets are added to the $conditions array with the unique
                // name of the corresponding group as key.
                $conditionArray = $groupOrCondition[self::CONDITION];
                $memberOf = $this->determineMemberOf($conditionArray);
                $this->groupedConditions[$memberOf][] = $conditionArray;
            } else {
                throw DrupalFilterException::neitherConditionNorGroup($filterName);
            }
        }
    }

    /**
     * @return array<string,array<int,DrupalFilterCondition>>
     */
    public function getGroupedConditions(): array
    {
        return $this->groupedConditions;
    }

    /**
     * @return array<string,DrupalFilterObject::AND|DrupalFilterObject::OR>
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
        if (array_key_exists(self::MEMBER_OF, $groupOrCondition)) {
            $memberOf = $groupOrCondition[self::MEMBER_OF];
            if (self::ROOT === $memberOf) {
                throw DrupalFilterException::memberOfRoot();
            }

            return $memberOf;
        }

        return self::ROOT;
    }

    /**
     * @param DrupalFilterGroup $group
     * @return DrupalFilterGroup
     * @throws DrupalFilterException
     */
    private function validateGroup(array $group): array
    {
        foreach ($group as $key => $value) {
            if (self::CONJUNCTION !== $key && self::MEMBER_OF !== $key) {
                throw DrupalFilterException::unknownGroupField($key);
            }
        }
        $conjunctionString = $group[self::CONJUNCTION];
        switch ($conjunctionString) {
            case self::AND:
            case self::OR:
                return $group;
            default:
                throw DrupalFilterException::conjunctionUnavailable($conjunctionString);
        }
    }
}
