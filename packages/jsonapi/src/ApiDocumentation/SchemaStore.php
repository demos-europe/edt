<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use function array_key_exists;
use ArrayAccess;
use cebe\openapi\spec\Schema;
use function get_class;
use function is_object;
use RuntimeException;
use UnexpectedValueException;

/**
 * Store OpenAPI Schema Definitions for reuse.
 */
class SchemaStore implements ArrayAccess
{
    /**
     * Schema definitions keyed by type name.
     *
     * @var array<string,Schema>
     */
    private $schemas = [];

    public function has(string $schemaName): bool
    {
        return $this->offsetExists($schemaName);
    }

    public function findOrCreate(string $schemaName, callable $schemaBuilder): Schema
    {
        if (!$this->has($schemaName)) {
            $this->offsetSet($schemaName, $schemaBuilder());
        }

        return $this->offsetGet($schemaName);
    }

    public function getSchemaReference(string $schemaName): string
    {
        return sprintf('#/components/schemas/%s', $schemaName);
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->schemas);
    }

    public function offsetGet($offset): Schema
    {
        return $this->schemas[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (!is_object($value)) {
            throw new UnexpectedValueException('$value must be an object');
        }

        if ($value instanceof Schema) {
            $this->schemas[$offset] = $value;

            return;
        }

        throw new RuntimeException('Unexpected schema type: '.get_class($value));
    }

    public function offsetUnset($offset): void
    {
        throw new RuntimeException('Cannot remove schemas from the store');
    }

    public function all(): array
    {
        return $this->schemas;
    }
}
