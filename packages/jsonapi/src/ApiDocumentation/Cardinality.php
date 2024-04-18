<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

enum Cardinality
{
    case TO_MANY;
    case TO_ONE;

    public function equals(Cardinality $relationship): bool
    {
        return $this === $relationship;
    }
}
