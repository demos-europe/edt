<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-implements FunctionInterface<bool>
 */
class AllTrue implements FunctionInterface
{
    use FunctionBasedTrait;

    /**
     * @param FunctionInterface<bool> $firstFunction
     * @param FunctionInterface<bool> ...$additionalFunctions
     */
    public function __construct(FunctionInterface $firstFunction, FunctionInterface ...$additionalFunctions)
    {
        $this->setFunctions($firstFunction, ...$additionalFunctions);
    }

    public function apply(array $propertyValues): bool
    {
        return $this->evaluateAll(static function (bool $evaluationResult): bool {
            return !$evaluationResult;
        }, $propertyValues);
    }
}
