<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\Utilities\PathConverterTrait;

abstract class AbstractDrupalFilter implements DrupalFilterInterface
{
    use PathConverterTrait;
}
