<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-implements FunctionInterface<bool>
 */
class StringContains implements FunctionInterface
{
    use MultiFunctionTrait;

    /**
     * @param FunctionInterface<string|null> $contains
     * @param FunctionInterface<string|null> $contained
     */
    public function __construct(FunctionInterface $contains, FunctionInterface $contained, bool $caseSensitive = false)
    {
        $this->setFunctions($contains, $contained);
        $this->callback = static function (?string $containsResult, ?string $containedResult) use ($caseSensitive): bool {
            if (null === $containedResult || null === $containsResult) {
                return false;
            }
            return $caseSensitive
                ? false !== mb_strpos($containsResult, $containedResult)
                : false !== mb_stripos($containsResult, $containedResult);
        };
    }
}
