<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends MultiFunction<bool>
 */
class StringStartsWith extends MultiFunction
{
    /**
     * @param FunctionInterface<string> $contains
     * @param FunctionInterface<string> $contained
     */
    public function __construct(FunctionInterface $contains, FunctionInterface $contained, bool $caseSensitive = false)
    {
        parent::__construct(
            static function (?string $contains, ?string $contained) use ($caseSensitive): bool {
                if (null === $contained || null === $contains) {
                    return false;
                }
                return $caseSensitive
                    ? 0 === mb_strpos($contains, $contained)
                    : 0 === mb_stripos($contains, $contained);
            },
            $contains,
            $contained
        );
    }
}
