<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface OperatorProviderInterface
{
    /**
     * Returns all Drupal operator names supported by this instance.
     *
     * @return list<non-empty-string>
     */
    public function getAllOperatorNames(): array;

    /**
     * @param non-empty-string                       $operatorName
     * @param mixed|null                             $value
     * @param non-empty-list<non-empty-string> $path
     *
     * @return TCondition
     */
    public function createOperator(string $operatorName, $value, array $path): PathsBasedInterface;
}
