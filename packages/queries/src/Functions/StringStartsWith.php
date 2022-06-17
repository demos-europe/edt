<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-implements FunctionInterface<bool>
 */
class StringStartsWith implements FunctionInterface
{
    use MultiFunctionTrait;

    /**
     * @param FunctionInterface<string> $contains
     * @param FunctionInterface<string> $contained
     */
    public function __construct(FunctionInterface $contains, FunctionInterface $contained, bool $caseSensitive = false)
    {
        $this->setFunctions($contains, $contained);
        $this->callback = static function (?string $contains, ?string $contained) use ($caseSensitive): bool {
            if (null === $contained || null === $contains) {
                return false;
            }
            return $caseSensitive
                ? 0 === mb_strpos($contains, $contained)
                : 0 === mb_stripos($contains, $contained);
        };
    }
}
