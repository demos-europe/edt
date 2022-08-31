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
 * Provides functions to convert data from HTTP requests into {@link FilterGroup} instances.
 *
 * The data is expected to be in the format defined by the Drupal JSON:API filter specification.
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
     * @var ConditionFactoryInterface
     */
    protected $conditionFactory;
    /**
     * @var ConditionParserInterface
     */
    private $conditionParser;

    public function __construct(ConditionFactoryInterface $conditionFactory, ConditionParserInterface $conditionParser)
    {
        $this->conditionFactory = $conditionFactory;
        $this->conditionParser = $conditionParser;
    }

    /**
     * @param array<string,array{condition: array{operator?: string, memberOf?: string, value?: mixed, path: string}}|array{group: array{memberOf?: string, conjunction: string}}> $groupsAndConditions
     * @return FunctionInterface<bool>
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
                if (DrupalFilterObject::ROOT_KEY === $bucketName) {
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
                    $parentGroupKey = $groupNameToMemberOf[$bucketName] ?? DrupalFilterObject::ROOT_KEY;
                    $conditionsToMerge = $conditions[$bucketName];
                    $conditions[$parentGroupKey][] = 1 === count($conditionsToMerge)
                        ? array_pop($conditionsToMerge)
                        : $this->createGroup($conjunction, ...$conditionsToMerge);
                    unset($conditions[$bucketName], $groupNameToMemberOf[$bucketName]);
                }
            }
        }

        // After having merged merged and added all buckets to the root bucket we
        // can merge it too and return the resulting root condition.
        $rootConditions = $conditions[DrupalFilterObject::ROOT_KEY] ?? [];
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
     * @param FunctionInterface<bool> $condition
     * @param FunctionInterface<bool> ...$conditions
     * @return FunctionInterface<bool>
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
        return 1 === count($conditions) && DrupalFilterObject::ROOT_KEY === array_key_first($conditions);
    }

    /**
     * @param array<string,array<int,array{operator?: string, memberOf?: string, value?: mixed, path: string}>> $groupedConditions
     * @return array<string,array<int,FunctionInterface<bool>>>
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
