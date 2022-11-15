<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\ConditionFactory\PathsBasedConditionGroupFactoryInterface;
use EDT\JsonApi\RequestHandling\FilterParserInterface;
use EDT\Querying\Contracts\ConditionParserInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use function count;
use function in_array;

/**
 * Provides functions to convert data from HTTP requests into condition instances.
 *
 * The data is expected to be in the format defined by the Drupal JSON:API filter specification.
 *
 * @phpstan-type DrupalValue = string|float|int|bool|array<int|string, mixed>|null
 * @phpstan-type DrupalFilterGroup = array{
 *            conjunction: 'AND'|'OR',
 *            memberOf?: non-empty-string
 *          }
 * @phpstan-type DrupalFilterCondition = array{
 *            path: non-empty-string,
 *            value?: DrupalValue,
 *            operator?: non-empty-string,
 *            memberOf?: non-empty-string
 *          }
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template-implements FilterParserInterface<array<non-empty-string, array{condition: DrupalFilterCondition}|array{group: DrupalFilterGroup}>, TCondition>
 */
class DrupalFilterParser implements FilterParserInterface
{
    /**
     * The maximum number of steps we make inside the tree to be built from the given condition groups.
     *
     * Exceeding this count indicates a that a group references itself as parent, directly or indirectly.
     *
     * If this count does not suffice for a (realistic) use case it can be increased further. Just
     * keep DoS attacks in mind when doing so.
     */
    private const MAX_ITERATIONS = 5000;

    /**
     * The key identifying a field as data for a filter group.
     */
    public const GROUP = 'group';

    /**
     * Any condition in the group must apply.
     */
    public const OR = 'OR';

    /**
     * This group/condition key is reserved and can not be used in a request.
     *
     * The value is not specified by Drupal's JSON:API filter documentation. However,
     * it is used by Drupal's implementation and was thus adopted here and preferred over
     * alternatives like 'root' or '' (empty string).
     */
    public const ROOT = '@root';

    /**
     * The key of the field determining which filter group a condition or a subgroup is a member
     * of.
     */
    public const MEMBER_OF = 'memberOf';

    /**
     * All conditions in the group must apply.
     */
    public const AND = 'AND';

    /**
     * The key identifying a field as data for a filter condition.
     */
    public const CONDITION = 'condition';

    /**
     * The key for the field in which "AND" or "OR" is stored.
     */
    public const CONJUNCTION = 'conjunction';

    /**
     * @var string
     */
    public const PATH = 'path';

    /**
     * @var string
     */
    public const OPERATOR = 'operator';

    /**
     * @var string
     */
    public const VALUE = 'value';

    /**
     * @var PathsBasedConditionGroupFactoryInterface<TCondition>
     */
    protected PathsBasedConditionGroupFactoryInterface $conditionGroupFactory;

    /**
     * @var ConditionParserInterface<DrupalFilterCondition, TCondition>
     */
    private ConditionParserInterface $conditionParser;

    private DrupalFilterValidator $filterValidator;

    /**
     * @param PathsBasedConditionGroupFactoryInterface<TCondition>        $conditionGroupFactory
     * @param ConditionParserInterface<DrupalFilterCondition, TCondition> $conditionParser
     */
    public function __construct(
        PathsBasedConditionGroupFactoryInterface $conditionGroupFactory,
        ConditionParserInterface $conditionParser,
        DrupalFilterValidator $filterValidator
    ) {
        $this->conditionGroupFactory = $conditionGroupFactory;
        $this->conditionParser = $conditionParser;
        $this->filterValidator = $filterValidator;
    }

    /**
     * The returned conditions are to be applied in an `AND` manner, i.e. all conditions must
     * match for an entity to match the Drupal filter. An empty error being returned means that
     * all entities match, as there are no restrictions.
     *
     * @param array<non-empty-string, array{condition: DrupalFilterCondition}|array{group: DrupalFilterGroup}> $filter
     *
     * @return list<TCondition>
     *
     * @throws DrupalFilterException
     */
    public function parseFilter($filter): array
    {
        $this->filterValidator->validateFilter($filter);
        $drupalFilter = new DrupalFilter($filter);
        $groupedConditions = $drupalFilter->getGroupedConditions();
        $conditions = $this->parseConditions($groupedConditions);

        // If no buckets with conditions exist we can return right away
        if (0 === count($conditions)) {
            return [];
        }

        $groupNameToMemberOf = $drupalFilter->getGroupNameToMemberOf();

        // We use the indices as information source and work on the $conditions
        // array only.

        // The buckets may be stored in a flat list, but logically we're working with a
        // tree of buckets. (Except if the request contained a group circle. Then
        // it is not a tree anymore, and we can't parse it.) To merge the buckets we need
        // to process that tree from the leaves up. To do so we search for buckets that
        // are not needed as parent group by any other bucket. These buckets are merged
        // into a single condition, which is then added to its parent bucket. This is
        // repeated until only the root bucket remains.
        $emergencyCounter = self::MAX_ITERATIONS;
        while (0 !== count($conditions) && !$this->hasReachedRootGroup($conditions)) {
            if (0 > --$emergencyCounter) {
                throw DrupalFilterException::emergencyAbort(self::MAX_ITERATIONS);
            }
            foreach ($conditions as $bucketName => $bucket) {
                if (self::ROOT === $bucketName) {
                    continue;
                }

                // If no conjunction definition for this group name exists we can remove it,
                // as the specification says to ignore such groups.
                if (!$drupalFilter->hasGroup($bucketName)) {
                    unset($conditions[$bucketName]);
                    continue;
                }

                // If the current bucket is not used as parent by any other group
                // then we can merge it and move the merged result into the parent
                // group. Afterwards the entry must be removed from the index to
                // mark it as no longer needed as by a parent.
                $usedAsParentGroup = in_array($bucketName, $groupNameToMemberOf, true);
                if (!$usedAsParentGroup) {
                    $conjunction = $drupalFilter->getGroupConjunction($bucketName);
                    $parentGroupKey = $drupalFilter->getFilterGroupParent($bucketName);
                    $conditionsToMerge = $conditions[$bucketName];
                    $additionalCondition = 1 === count($conditionsToMerge)
                        ? array_pop($conditionsToMerge)
                        : $this->createGroup($conjunction, $conditionsToMerge);
                    $conditions[$parentGroupKey][] = $additionalCondition;
                    unset($conditions[$bucketName], $groupNameToMemberOf[$bucketName]);
                }
            }
        }

        return $conditions[self::ROOT] ?? [];
    }

    /**
     * @param self::AND|self::OR $conjunction
     * @param non-empty-list<TCondition> $conditions
     * @return TCondition
     *
     * @throws DrupalFilterException
     */
    protected function createGroup(string $conjunction, array $conditions): PathsBasedInterface
    {
        switch ($conjunction) {
            case self::AND:
                return $this->conditionGroupFactory->allConditionsApply(...$conditions);
            case self::OR:
                return $this->conditionGroupFactory->anyConditionApplies(...$conditions);
            default:
                throw DrupalFilterException::conjunctionUnavailable($conjunction);
        }
    }

    /**
     * @param array<non-empty-string, list<TCondition|null>> $conditions
     */
    private function hasReachedRootGroup(array $conditions): bool
    {
        return 1 === count($conditions) && self::ROOT === array_key_first($conditions);
    }

    /**
     * @param array<non-empty-string, non-empty-list<DrupalFilterCondition>> $groupedConditions
     *
     * @return array<non-empty-string, non-empty-list<TCondition>>
     */
    protected function parseConditions(array $groupedConditions): array
    {
        return array_map(
            fn (array $conditionGroup): array => array_map(
                [$this->conditionParser, 'parseCondition'],
                $conditionGroup
            ),
            $groupedConditions
        );
    }
}
