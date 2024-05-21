<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Schema;
use EDT\Wrapping\Contracts\RelationshipInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use Exception;
use function array_key_exists;

/**
 * Create and store OpenAPI Schema Definitions for reuse.
 *
 * @internal
 */
class SchemaStore
{
    /**
     * Schema definitions keyed by type name.
     *
     * @var array<non-empty-string, Schema>
     */
    protected array $schemas = [];

    /**
     * @var array<non-empty-string, PropertyReadableTypeInterface<object>>
     */
    protected array $schemasToCreate = [];

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
     * @param PropertyReadableTypeInterface<object> $type
     * @param non-empty-string $typeName
     *
     * @return non-empty-string
     */
    public function noteTypeForSchemaCreationAndGetReference(PropertyReadableTypeInterface $type, string $typeName): string
    {
        if (!array_key_exists($typeName, $this->schemasToCreate) && !array_key_exists($typeName, $this->schemas)) {
            $this->schemasToCreate[$typeName] = $type;
        }

        return $this->getReference($typeName);
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

    public function createPendingSchemas(): void
    {
        // If there are schemas noted for creation we create them. The property may receive additional values on each
        // loop, so we check it every time for additional schemas to be created, until it is finally empty.
        while ([] !== $this->schemasToCreate) {
            // While creating schemas, new schemas may be detected and noted for creation, so we copy the property
            // array and clear it. After creating the schemas in our copy, the property will contain the additionally
            // detected schemas to be created.
            $schemasToCreate = $this->schemasToCreate;
            $this->schemasToCreate = [];

            foreach ($schemasToCreate as $typeName => $type) {
                // If the schema does not exist, it will be created. During the creation additional schemas may be
                // detected, but those are not automatically created, but noted for creation instead. This avoids
                // recursion circles.
                if (!array_key_exists($typeName, $this->schemas)) {
                    $this->schemas[$typeName] = $this->createTypeSchema($type, $typeName);
                }
            }
        }
    }

    public function reset(): void
    {
        $this->schemas = [];
        $this->schemasToCreate = [];
    }

    /**
     * @param PropertyReadableTypeInterface<object> $type
     * @param non-empty-string $typeName
     *
     * @throws TypeErrorException
     * @throws OpenApiGenerateException
     */
    protected function createTypeSchema(PropertyReadableTypeInterface $type, string $typeName): Schema
    {
        $readableProperties = $type->getReadability();

        $schemaProperties = array_map(
            function (RelationshipInterface $readability): array {
                $relationshipType = $readability->getRelationshipType();
                return [
                    '$ref' => $this->noteTypeForSchemaCreationAndGetReference($relationshipType, $relationshipType->getTypeName()),
                ];
            },
            $readableProperties->getRelationships()
        );

        foreach ($readableProperties->getAttributes() as $propertyName => $readability) {
            try {
                $schemaProperties[$propertyName] = $readability->getPropertySchema();
            } catch (Exception $exception) {
                throw OpenApiGenerateException::attributeType($propertyName, $typeName, $exception);
            }
        }

        return new Schema(['type' => 'object', 'properties' => $schemaProperties]);
    }
}
