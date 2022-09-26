<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\JsonApi\RequestHandling\FilterParserInterface;
use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\ConditionParserInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use function count;
use function in_array;

/**
 * Provides functions to convert data from HTTP requests into condition instances.
 *
 * The data is expected to be in the format defined by the Drupal JSON:API filter specification.
 *
 * @psalm-type DrupalFilterGroup = array{
 *            conjunction: non-empty-string,
 *            memberOf?: non-empty-string
 *          }
 * @psalm-type DrupalFilterCondition = array{
 *            path: non-empty-string,
 *            value?: mixed,
 *            operator?: non-empty-string,
 *            memberOf?: non-empty-string
 *          }
 * @template F of \EDT\Querying\Contracts\PathsBasedInterface
 * @template-implements FilterParserInterface<array<non-empty-string,array{condition: DrupalFilterCondition}|array{group: DrupalFilterGroup}>, F>
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
     * @var ConditionFactoryInterface<F>
     */
    protected $conditionFactory;

    /**
     * @var ConditionParserInterface<DrupalFilterCondition, F>
     */
    private $conditionParser;

    /**
     * @var DrupalFilterValidator
     */
    private $filterValidator;

    /**
     * @param ConditionFactoryInterface<F>                       $conditionFactory
     * @param ConditionParserInterface<DrupalFilterCondition, F> $conditionParser
     */
    public function __construct(
        ConditionFactoryInterface $conditionFactory,
        ConditionParserInterface $conditionParser,
        DrupalFilterValidator $filterValidator
    ) {
        $this->conditionFactory = $conditionFactory;
        $this->conditionParser = $conditionParser;
        $this->filterValidator = $filterValidator;
    }

    /**
     * The returned conditions are to be applied in an `AND` manner, i.e. all conditions must
     * match for an entity to match the Drupal filter. An empty error being returned means that
     * all entities match, as there are no restrictions.
     *
     * @param array<non-empty-string,array{condition: DrupalFilterCondition}|array{group: DrupalFilterGroup}> $filter
     *
     * @return list<F>
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
                        : $this->createGroup($conjunction, ...$conditionsToMerge);
                    $conditions[$parentGroupKey][] = $additionalCondition;
                    unset($conditions[$bucketName], $groupNameToMemberOf[$bucketName]);
                }
            }
        }

        return $conditions[self::ROOT] ?? [];
    }

    /**
     * @param F $condition
     * @param F ...$conditions
     * @return F
     *
     * @throws DrupalFilterException
     */
    protected function createGroup(string $conjunction, PathsBasedInterface $condition, PathsBasedInterface ...$conditions): PathsBasedInterface
    {
        switch ($conjunction) {
            case self::AND:
                return $this->conditionFactory->allConditionsApply($condition, ...$conditions);
            case self::OR:
                return $this->conditionFactory->anyConditionApplies($condition, ...$conditions);
            default:
                throw DrupalFilterException::conjunctionUnavailable($conjunction);
        }
    }

    /**
     * @param array<non-empty-string, list<F|null>> $conditions
     */
    private function hasReachedRootGroup(array $conditions): bool
    {
        return 1 === count($conditions) && self::ROOT === array_key_first($conditions);
    }

    /**
     * @param array<non-empty-string, list<DrupalFilterCondition>> $groupedConditions
     *
     * @return array<non-empty-string, list<F>>
     */
    private function parseConditions(array $groupedConditions): array
    {
        return array_map(function (array $conditionGroup): array {
            return array_map(function (array $condition): PathsBasedInterface {
                return $this->conditionParser->parseCondition($condition);
            }, $conditionGroup);
        }, $groupedConditions);
    }
}
