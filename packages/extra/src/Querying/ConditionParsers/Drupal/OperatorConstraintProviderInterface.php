<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use Symfony\Component\Validator\Constraint;

interface OperatorConstraintProviderInterface
{
    /**
     * All operators supported by this instance.
     *
     * Returns a mapping from the operator name to the constraints to be applied on the *condition* the operator resides in.
     *
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getSupportedOperators(): array;
}
