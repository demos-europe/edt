<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethodFactories;

use EDT\Querying\Contracts\PathAdjustableInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Querying\Utilities\PathConverterTrait;

interface SortMethodInterface extends PathAdjustableInterface
{
    /**
     * @return non-empty-string
     */
    public function getAsString(): string;
}
