<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethods;

class Ascending extends AbstractSortMethod
{
    public function __toString(): string
    {
        return "Ascending with base function: $this->target";
    }
}
