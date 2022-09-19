<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

class MessageFormatter
{
    /**
     * Wraps each given property into quotes and concatenates them with a comma.
     *
     * @param list<string> $properties
     */
    public function propertiesToString(array $properties): string
    {
        $properties = array_map(static function (string $property): string {
            return "`$property`";
        }, $properties);

        return implode(', ', $properties);
    }
}
