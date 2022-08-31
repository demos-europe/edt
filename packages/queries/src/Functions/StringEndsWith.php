<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends AbstractMultiFunction<bool, string|null, array{0: string|null, 1: string|null}>
 */
class StringEndsWith extends AbstractMultiFunction
{
    /**
     * @var bool
     */
    private $caseSensitive;

    /**
     * @param FunctionInterface<string|null> $contains
     * @param FunctionInterface<string|null> $contained
     */
    public function __construct(FunctionInterface $contains, FunctionInterface $contained, bool $caseSensitive)
    {
        parent::__construct($contains, $contained);
        $this->caseSensitive = $caseSensitive;
    }

    protected function reduce(array $functionResults)
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
