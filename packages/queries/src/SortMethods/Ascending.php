<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethods;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\SortException;
use EDT\Querying\Contracts\SortMethodInterface;

class Ascending implements SortMethodInterface
{
    use SortMethodTrait;

    /**
     * @var FunctionInterface<mixed>
     */
    private $target;

    /**
     * @param FunctionInterface<mixed> $target
     */
    public function __construct(FunctionInterface $target)
    {
        $this->target = $target;
    }

    public function getPropertyPaths(): iterable
    {
        return $this->target->getPropertyPaths();
    }

    /**
     * @return int
     * @throws SortException
     */
    public function evaluate(array $propertyValuesA, array $propertyValuesB): int
    {
        $valueA = $this->target->apply($propertyValuesA);
        $valueB = $this->target->apply($propertyValuesB);
        return $this->evaluateSinglePath($valueA, $valueB);
    }

    public function __toString(): string
    {
        return "Ascending with base function: $this->target";
    }
}
