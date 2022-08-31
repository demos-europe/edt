<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends MultiFunction<bool>
 */
class StringContains extends MultiFunction
{
    /**
     * @param FunctionInterface<string|null> $contains
     * @param FunctionInterface<string|null> $contained
     */
    public function __construct(FunctionInterface $contains, FunctionInterface $contained, bool $caseSensitive = false)
    {
        parent::__construct(
            static function (?string $containsResult, ?string $containedResult) use ($caseSensitive): bool {
                if (null === $containedResult || null === $containsResult) {
                    return false;
                }
                return $caseSensitive
                    ? false !== mb_strpos($containsResult, $containedResult)
                    : false !== mb_stripos($containsResult, $containedResult);
            },
            $contains,
            $contained
        );
    }
}
