<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends MultiFunction<bool>
 */
class StringEndsWith extends MultiFunction
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
                $needleLength = mb_strlen($contained);
                if (0 === $needleLength) {
                    // empty string is considered part of all strings
                    return true;
                }
                return $caseSensitive
                    ? mb_substr($contains, -$needleLength) === $contained
                    : mb_strtolower(mb_substr($contains, -$needleLength)) === mb_strtolower($contained);
            },
            $contains,
            $contained
        );
    }
}
