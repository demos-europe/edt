<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

/**
 * @template-extends AbstractSingleFunction<bool, mixed|null>
 */
class IsNull extends AbstractSingleFunction
{
    public function apply(array $propertyValues): bool
    {
        return null === $this->getOnlyFunction()->apply($propertyValues);
    }
}
