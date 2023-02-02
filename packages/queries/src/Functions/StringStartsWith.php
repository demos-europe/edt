<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends AbstractMultiFunction<bool, string|null, array{0: string|null, 1: string|null}>
 */
class StringStartsWith extends AbstractMultiFunction
{
    /**
     * @param FunctionInterface<string|null> $contains
     * @param FunctionInterface<string|null> $contained
     */
    public function __construct(
        FunctionInterface $contains,
        FunctionInterface $contained,
        private readonly bool $caseSensitive
    ) {
        parent::__construct($contains, $contained);
    }

    protected function reduce(array $functionResults): bool
    {
        [$contains, $contained] = $functionResults;

        if (null === $contained || null === $contains) {
            return false;
        }
        return $this->caseSensitive
            ? 0 === mb_strpos($contains, $contained)
            : 0 === mb_stripos($contains, $contained);
    }
}
