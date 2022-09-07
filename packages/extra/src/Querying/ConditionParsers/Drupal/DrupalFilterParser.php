<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\ConditionParserInterface;
use EDT\Querying\Contracts\FunctionInterface;
use function array_key_exists;
use function count;
use function in_array;

/**
 * Provides functions to convert data from HTTP requests into {@link FunctionInterface} instances.
 *
 * The data is expected to be in the format defined by the Drupal JSON:API filter specification.
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
 * @template F of FunctionInterface<bool>
 */
class DrupalFilterParser
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
     * @var ConditionFactoryInterface<F>
     */
    protected $conditionFactory;
    /**
     * @var ConditionParserInterface<DrupalFilterCondition, F>
     */
    private $conditionParser;

    /**
     * @param ConditionFactoryInterface<F>                $conditionFactory
     * @param ConditionParserInterface<DrupalFilterCondition, F> $conditionParser
     */
    public function __construct(ConditionFactoryInterface $conditionFactory, ConditionParserInterface $conditionParser)
    {
        $this->conditionFactory = $conditionFactory;
        $this->conditionParser = $conditionParser;
    }

    /**
     * @param array<string,array{condition: DrupalFilterCondition}|array{group: DrupalFilterGroup}> $groupsAndConditions
     * @return F
     * @throws DrupalFilterException
     */
    public function createRootFromArray(array $groupsAndConditions): FunctionInterface
    {
        $filter = new DrupalFilterObject($groupsAndConditions);
        $conditions = $this->parseConditions($filter->getGroupedConditions());

        // If no buckets with conditions exist we can return right away
        if (0 === count($conditions)) {
            return $this->conditionFactory->true();
        }

        $groupNameToConjunction = $filter->getGroupNameToConjunction();
        $groupNameToMemberOf = $filter->getGroupNameToMemberOf();

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
        while (0 !== count($conditions) && !$this->reachedRootGroup($conditions)) {
            if (0 > --$emergencyCounter) {
                throw DrupalFilterException::emergencyAbort(self::MAX_ITERATIONS);
            }
            foreach ($conditions as $bucketName => $bucket) {
                if (DrupalFilterObject::ROOT === $bucketName) {
                    continue;
                }

                // If no conjunction definition for this group name exists we can remove it,
                // as the specification says to ignore such groups.
                if (!array_key_exists($bucketName, $groupNameToConjunction)) {
                    unset($conditions[$bucketName]);
                    continue;
                }

                // If the current bucket is not used as parent by any other group
                // then we can merge it and move the merged result into the parent
                // group. Afterwards the entry must be removed from the index to
                // mark it as no longer needed as by a parent.
                $usedAsParentGroup = in_array($bucketName, $groupNameToMemberOf, true);
                if (!$usedAsParentGroup) {
                    $conjunction = $groupNameToConjunction[$bucketName];
                    $parentGroupKey = $groupNameToMemberOf[$bucketName] ?? DrupalFilterObject::ROOT;
                    $conditionsToMerge = $conditions[$bucketName];
                    $additionalCondition = 1 === count($conditionsToMerge)
                        ? array_pop($conditionsToMerge)
                        : $this->createGroup($conjunction, ...$conditionsToMerge);
                    $conditions[$parentGroupKey][] = $additionalCondition;
                    unset($conditions[$bucketName], $groupNameToMemberOf[$bucketName]);
                }
            }
        }

        // After having merged and added all buckets to the root bucket we
        // can merge it too and return the resulting root condition.
        $rootConditions = $conditions[DrupalFilterObject::ROOT] ?? [];
        switch (count($rootConditions)) {
            case 0:
                return $this->conditionFactory->true();
            case 1:
                return array_pop($rootConditions);
            default:
                return $this->conditionFactory->allConditionsApply(...$rootConditions);
        }
    }

    /**
     * @param F $condition
     * @param F ...$conditions
     * @return F
     * @throws DrupalFilterException
     */
    protected function createGroup(string $conjunction, FunctionInterface $condition, FunctionInterface ...$conditions): FunctionInterface
    {
        switch ($conjunction) {
            case DrupalFilterObject::AND:
                return $this->conditionFactory->allConditionsApply($condition, ...$conditions);
            case DrupalFilterObject::OR:
                return $this->conditionFactory->anyConditionApplies($condition, ...$conditions);
            default:
                throw DrupalFilterException::conjunctionUnavailable($conjunction);
        }
    }

    /**
     * @param array<string,array<int,FunctionInterface<bool>|null>> $conditions
     * @return bool
     */
    private function reachedRootGroup(array $conditions): bool
    {
        return 1 === count($conditions) && DrupalFilterObject::ROOT === array_key_first($conditions);
    }

    /**
     * @param array<string,array<int,DrupalFilterCondition>> $groupedConditions
     * @return array<string,array<int,F>>
     */
    private function parseConditions(array $groupedConditions): array
    {
        return array_map(function (array $conditionGroup): array {
            return array_map(function (array $condition): FunctionInterface {
                return $this->conditionParser->parseCondition($condition);
            }, $conditionGroup);
        }, $groupedConditions);
    }
}
