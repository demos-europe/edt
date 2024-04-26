<?php

declare(strict_types=1);

namespace EDT\Querying\Conditions;

use InvalidArgumentException;

class ConditionValueTypeException extends InvalidArgumentException
{
    /**
     * @param non-empty-list<non-empty-string> $expected
     */
    public function __construct(
        protected array $expected,
        protected mixed $actualValue
    ) {
        $expectedString = implode(', ', $expected);
        $actualString = gettype($actualValue);

        parent::__construct("Got value with type `$actualString`, but expected any of the following: $expectedString.");
    }
}
