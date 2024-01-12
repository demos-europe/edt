<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Func;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<string|null>
 */
class UpperCase extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<string|null> $baseFunction
     */
    public function __construct(ClauseFunctionInterface $baseFunction)
    {
        parent::__construct(
            new \EDT\Querying\Functions\UpperCase($baseFunction),
            $baseFunction
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): Func
    {
        return $this->expr->upper($this->getOnlyClause()->asDql($valueReferences, $propertyAliases, $mainEntityAlias));
    }
}
