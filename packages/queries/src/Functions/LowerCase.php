<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use function is_string;

/**
 * @template-implements FunctionInterface<string|null>
 */
class LowerCase implements FunctionInterface
{
    use FunctionBasedTrait;

    /**
     * @param FunctionInterface<string> $baseFunction
     */
    public function __construct(FunctionInterface $baseFunction)
    {
        $this->setFunctions($baseFunction);
    }

    public function apply(array $propertyValues): ?string
    {
        $baseFunctionResult = $this->getOnlyFunction()->apply($propertyValues);
        if (!is_string($baseFunctionResult)) {
            return null;
        }
        return mb_strtolower($baseFunctionResult);
    }
}
