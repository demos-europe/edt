<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Andx;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<bool>
 */
class AllTrue extends \EDT\Querying\Functions\AllEqual implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @phpstan-param ClauseFunctionInterface<bool> $firstFunction
     * @phpstan-param ClauseFunctionInterface<bool> ...$additionalFunctions
     */
    public function __construct(ClauseFunctionInterface $firstFunction, ClauseFunctionInterface ...$additionalFunctions)
    {
        parent::__construct(new \EDT\Querying\Functions\Value(true), $firstFunction, ...$additionalFunctions);
        $this->setClauses($firstFunction, ...$additionalFunctions);
    }

    /**
     * @return Andx
     */
    public function asDql(array $valueReferences, array $propertyAliases): Andx
    {
        return new Andx($this->getDqls($valueReferences, $propertyAliases));
    }
}
