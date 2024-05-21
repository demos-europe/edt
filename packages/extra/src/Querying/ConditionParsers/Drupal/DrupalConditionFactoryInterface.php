<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

/**
 * @template TCondition
 * @phpstan-import-type DrupalValue from DrupalFilterParser
 */
interface DrupalConditionFactoryInterface extends OperatorConstraintProviderInterface
{
    /**
     * @param non-empty-string $operatorName
     * @param DrupalValue $value
     * @param non-empty-list<non-empty-string>|null $path
     *
     * @return TCondition
     *
     * @throws DrupalFilterException if the given operator name is not supported
     */
    public function createConditionWithValue(string $operatorName, array|string|int|float|bool|null $value, ?array $path);

    /**
     * @param non-empty-string $operatorName
     * @param non-empty-list<non-empty-string>|null $path
     *
     * @return TCondition
     *
     * @throws DrupalFilterException if the given operator name is not supported
     */
    public function createConditionWithoutValue(string $operatorName, ?array $path);
}
