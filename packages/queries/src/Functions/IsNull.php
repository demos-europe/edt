<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-implements FunctionInterface<bool>
 */
class IsNull implements FunctionInterface
{
    use FunctionBasedTrait;

    /**
     * @param FunctionInterface<mixed> $baseFunction
     */
    public function __construct(FunctionInterface $baseFunction)
    {
        $this->setFunctions($baseFunction);
    }

    public function apply(array $propertyValues): bool
    {
        return null === $this->getOnlyFunction()->apply($propertyValues);
    }
}
