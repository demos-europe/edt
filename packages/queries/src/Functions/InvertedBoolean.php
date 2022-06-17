<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionException;
use EDT\Querying\Contracts\FunctionInterface;
use function is_bool;

/**
 * @template-implements FunctionInterface<bool>
 */
class InvertedBoolean implements FunctionInterface
{
    use FunctionBasedTrait;

    /**
     * @param FunctionInterface<bool> $baseFunction
     */
    public function __construct(FunctionInterface $baseFunction)
    {
        $this->setFunctions($baseFunction);
    }

    /**
     * @throws FunctionException
     */
    public function apply(array $propertyValues): bool
    {
        $baseFunctionResult = $this->getOnlyFunction()->apply($propertyValues);
        if (is_bool($baseFunctionResult)) {
            return !$baseFunctionResult;
        }
        throw FunctionException::invalidReturnType('bool', $baseFunctionResult);
    }
}
