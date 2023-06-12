<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use cebe\openapi\exceptions\TypeErrorException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\RelationshipReadabilityInterface;
use Exception;
use Safe\Exceptions\StringsException;
use function array_key_exists;
use cebe\openapi\spec\Schema;

/**
 * Create and store OpenAPI Schema Definitions for reuse.
 */
class SchemaStore
{
    /**
     * Schema definitions keyed by type name.
     *
     * @var array<non-empty-string, Schema>
     */
    private array $schemas = [];

    /**
     * @param non-empty-string $schemaName
     * @param callable(): Schema $schemaBuilder
     */
    public function findOrCreate(string $schemaName, callable $schemaBuilder): Schema
    {
        if (!array_key_exists($schemaName, $this->schemas)) {
            $value = $schemaBuilder();
            $this->schemas[$schemaName] = $value;

            return $value;
        }

        return $this->schemas[$schemaName];
    }

    /**
     * @param TransferableTypeInterface<PathsBasedInterface, PathsBasedInterface, object> $type
     *
     * @return non-empty-string
     *
     * @throws StringsException
     * @throws TypeErrorException
     */
    public function createTypeSchemaAndGetReference(TransferableTypeInterface $type): string
    {
        $typeIdentifier = $type->getTypeName();
        if (!array_key_exists($typeIdentifier, $this->schemas)) {
            $this->schemas[$typeIdentifier] = $this->createTypeSchema($type);
        }

        return $this->getReference($type->getTypeName());
    }

    /**
     * @param non-empty-string $schemaName
     *
     * @return non-empty-string
     */
    public function getReference(string $schemaName): string
    {
        return "#/components/schemas/$schemaName";
    }

    /**
     * @return array<non-empty-string, Schema>
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * @param TransferableTypeInterface<PathsBasedInterface, PathsBasedInterface, object> $type
     *
     * @throws TypeErrorException
     * @throws StringsException
     */
    protected function createTypeSchema(TransferableTypeInterface $type): Schema
    {
        $readableProperties = $type->getReadableProperties();

        $schemaProperties = array_map(
            fn (RelationshipReadabilityInterface $readability): array => [
                '$ref' => $this->createTypeSchemaAndGetReference($readability->getRelationshipType()),
            ],
            $readableProperties->getRelationships()
        );

        foreach ($readableProperties->getAttributes() as $propertyName => $readability) {
            try {
                $schemaProperties[$propertyName] = $readability->getPropertySchema();
            } catch (Exception $exception) {
                throw OpenApiGenerateException::attributeType($propertyName, $type->getTypeName(), $exception);
            }
        }

        return new Schema(['type' => 'object', 'properties' => $schemaProperties]);
    }
}
