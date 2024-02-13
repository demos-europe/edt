<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\PathsBasedInterface;
use Symfony\Component\Validator\Constraint;

/**
 * @template TCondition of PathsBasedInterface
 * @phpstan-import-type DrupalValue from DrupalFilterParser
 */
interface DrupalConditionFactoryInterface
{
    /**
     * All operators supported by this instance.
     *
     * Returns a mapping from the operator name to the constraints to be applied on the *condition* the operator resides in.
     *
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getSupportedOperators(): array;

    /**
     * @param non-empty-string $operatorName
     * @param DrupalValue $value
     * @param non-empty-list<non-empty-string> $path
     *
     * @return TCondition
     *
     * @throws DrupalFilterException if the given operator name is not supported
     */
    public function createConditionWithValue(string $operatorName, array|string|int|float|bool|null $value, array $path): PathsBasedInterface;

    /**
     * @param non-empty-string $operatorName
     * @param non-empty-list<non-empty-string> $path
     *
     * @return TCondition
     *
     * @throws DrupalFilterException if the given operator name is not supported
     */
    public function createConditionWithoutValue(string $operatorName, array $path): PathsBasedInterface;
}
