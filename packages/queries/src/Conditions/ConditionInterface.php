<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use EDT\Querying\Contracts\PathsBasedInterface;
use Symfony\Component\Validator\Constraint;

/**
 * @template TCondition of PathsBasedInterface
 */
interface ConditionInterface
{
    /**
     * @return list<Constraint>
     */
    public function getFormatConstraints(): array;

    /**
     * @return non-empty-string
     */
    public function getOperator(): string;
}
