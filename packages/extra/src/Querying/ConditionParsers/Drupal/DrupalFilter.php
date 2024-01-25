<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use function array_key_exists;

/**
 * Separates the conditions from the group definitions and builds two
 * indices from the group definitions. One for the {@link DrupalFilterParser::AND}/{@link DrupalFilterParser::OR}
 * conjunctions and one for the {@link DrupalFilterParser::MEMBER_OF} field value.
 *
 * This implementation will not apply any defaults or other changes to the conditions themselves, but simply
 * restructures the grouping from a fully reference-based approach into a slightly more structured one, that
 * makes further processing and transformation easier.
 *
 * @phpstan-import-type DrupalFilterGroup from DrupalFilterParser
 * @phpstan-import-type DrupalFilterCondition from DrupalFilterParser
 */
class DrupalFilter
{
    /**
     * @var array<non-empty-string, non-empty-list<DrupalFilterCondition>>
     */
    private array $groupedConditions = [];
    /**
     * @var array<non-empty-string, DrupalFilterParser::AND|DrupalFilterParser::OR>
     */
    private array $groupNameToConjunction = [];
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private array $groupNameToMemberOf = [];

    /**
     * This constructor receives the group definitions and conditions as a "flat" array, meaning all
     * group definitions and all conditions are on the first level, each having
     * a unique name.
     *
     * @param array<non-empty-string, array{condition: DrupalFilterCondition}|array{group: DrupalFilterGroup}> $groupsAndConditions
     * @throws DrupalFilterException
     */
    public function __construct(array $groupsAndConditions)
    {
        // One special name is reserved for internal usage by the drupal filter specification.
        if (array_key_exists(DrupalFilterParser::ROOT, $groupsAndConditions)) {
            throw DrupalFilterException::rootKeyUsed();
        }

        foreach ($groupsAndConditions as $filterName => $groupOrCondition) {
            if (array_key_exists(DrupalFilterParser::GROUP, $groupOrCondition)) {
                // If an item defines a group its structure will be simplified,
                // and it is added to the groups with its unique name as key.
                $group = $groupOrCondition[DrupalFilterParser::GROUP];
                $this->groupNameToConjunction[$filterName] = $group[DrupalFilterParser::CONJUNCTION];
                $this->groupNameToMemberOf[$filterName] = $this->determineMemberOf($group);
            } elseif (array_key_exists(DrupalFilterParser::CONDITION, $groupOrCondition)) {
                // If an item is a condition then a condition object will be created from it.
                // That object is then added not directly to the conditions but to a bucket
                // instead. Each bucket contains all conditions that belong into the same
                // group. The buckets are added to the $conditions array with the unique
                // name of the corresponding group as key.
                $conditionArray = $groupOrCondition[DrupalFilterParser::CONDITION];
                $memberOf = $this->determineMemberOf($conditionArray);
                $this->groupedConditions[$memberOf][] = $conditionArray;
            } else {
                throw DrupalFilterException::neitherConditionNorGroup($filterName);
            }
        }
    }

    /**
     * @return array<non-empty-string, non-empty-list<DrupalFilterCondition>>
     */
    public function getGroupedConditions(): array
    {
        return $this->groupedConditions;
    }

    /**
     * @param non-empty-string $groupName
     * @return DrupalFilterParser::AND|DrupalFilterParser::OR
     */
    public function getGroupConjunction(string $groupName): string
    {
        return $this->groupNameToConjunction[$groupName];
    }

    /**
     * @param non-empty-string $groupName
     */
    public function hasGroup(string $groupName): bool
    {
        return array_key_exists($groupName, $this->groupNameToConjunction);
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     */
    public function getGroupNameToMemberOf(): array
    {
        return $this->groupNameToMemberOf;
    }

    /**
     * @param non-empty-string $groupName
     *
     * @return non-empty-string
     */
    public function getFilterGroupParent(string $groupName): string
    {
        return $this->groupNameToMemberOf[$groupName] ?? DrupalFilterParser::ROOT;
    }

    /**
     * Get the unique name of the parent group of the given group or condition.
     *
     * @param array{memberOf?: non-empty-string} $groupOrCondition
     *
     * @return non-empty-string
     *
     * @throws DrupalFilterException
     */
    protected function determineMemberOf(array $groupOrCondition): string
    {
        if (array_key_exists(DrupalFilterParser::MEMBER_OF, $groupOrCondition)) {
            $memberOf = $groupOrCondition[DrupalFilterParser::MEMBER_OF];
            if (DrupalFilterParser::ROOT === $memberOf) {
                throw DrupalFilterException::memberOfRoot();
            }

            return $memberOf;
        }

        return DrupalFilterParser::ROOT;
    }
}
