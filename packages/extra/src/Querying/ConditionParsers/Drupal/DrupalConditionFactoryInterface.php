<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @phpstan-import-type DrupalValue from DrupalFilterParser
 */
interface DrupalConditionFactoryInterface
{
    /**
     * Returns all Drupal operator names supported by this instance.
     *
     * @return list<non-empty-string>
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
    public function createCondition(string $operatorName, array|string|int|float|bool|null $value, array $path): PathsBasedInterface;
}
