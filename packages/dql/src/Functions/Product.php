<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Math;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<int|float>
 */
class Product extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<numeric> $firstFactor
     * @param ClauseFunctionInterface<numeric> $secondFactor
     * @param ClauseFunctionInterface<numeric> ...$additionalFactors
     */
    public function __construct(ClauseFunctionInterface $firstFactor, ClauseFunctionInterface $secondFactor, ClauseFunctionInterface ...$additionalFactors)
    {
        parent::__construct(
            new \EDT\Querying\Functions\Product($firstFactor, $secondFactor, ...$additionalFactors),
            $firstFactor, $secondFactor, ...$additionalFactors
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): Composite|Math|Func|Comparison|string
    {
        $dqlClauses = $this->getDqls($valueReferences, $propertyAliases, $mainEntityAlias);
        $initial = array_shift($dqlClauses);
        // do not typehint the callback parameters as strings, as we must not implicitly invoke __toString
        return array_reduce($dqlClauses, [$this->expr, 'prod'], $initial);
    }
}
