<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use Countable;
use EDT\Querying\Contracts\FunctionInterface;
use function count;

/**
 * @template-implements FunctionInterface<int>
 */
class Size implements FunctionInterface
{
    use FunctionBasedTrait;

    /**
     * @param FunctionInterface<Countable|array<mixed>> $baseFunction
     */
    public function __construct(FunctionInterface $baseFunction)
    {
        $this->setFunctions($baseFunction);
    }

    public function apply(array $propertyValues): int
    {
        return count($this->getOnlyFunction()->apply($propertyValues));
    }
}

