<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

class MessageFormatter
{
    /**
     * Wraps each given property between backticks and concatenates them with a comma.
     *
     * @param non-empty-list<non-empty-string> $properties
     */
    public function propertiesToString(array $properties): string
    {
        $properties = array_map(static fn (string $property): string => "`$property`", $properties);

        return implode(', ', $properties);
    }
}
