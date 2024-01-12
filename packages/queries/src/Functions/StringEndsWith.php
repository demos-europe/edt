<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends AbstractMultiFunction<bool, string|null, array{string|null, string|null}>
 */
class StringEndsWith extends AbstractMultiFunction
{
    /**
     * @param FunctionInterface<string|null> $contains
     * @param FunctionInterface<string|null> $contained
     */
    public function __construct(
        FunctionInterface $contains,
        FunctionInterface $contained,
        protected readonly bool $caseSensitive
    ) {
        parent::__construct($contains, $contained);
    }

    protected function reduce(array $functionResults): bool
    {
        [$contains, $contained] = $functionResults;

        if (null === $contained || null === $contains) {
            return false;
        }

        $needleLength = mb_strlen($contained);
        if (0 === $needleLength) {
            // empty string is considered part of all strings
            return true;
        }

        return $this->caseSensitive
            ? mb_substr($contains, -$needleLength) === $contained
            : mb_strtolower(mb_substr($contains, -$needleLength)) === mb_strtolower($contained);
    }
}
