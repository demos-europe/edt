<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use Webmozart\Assert\Assert;

/**
 * @template TOutput
 * @template TInput
 * @template-extends AbstractFunction<TOutput, TInput>
 */
abstract class AbstractSingleFunction extends AbstractFunction
{
    /**
     * In case the using implementation has set only one instance into {@link AbstractFunction::$functions}
     * you can use this method to easily access it. An exception will be thrown if not
     * exactly one {@link FunctionInterface} instance is set in {@link AbstractFunction::$functions}
     * when this method is called.
     *
     * @return FunctionInterface<TInput>
     */
    protected function getOnlyFunction(): FunctionInterface
    {
        Assert::count($this->functions, 1);
        return $this->functions[0];
    }
}
