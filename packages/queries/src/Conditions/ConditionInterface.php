<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use Symfony\Component\Validator\Constraint;

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
