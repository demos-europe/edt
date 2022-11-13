<?php

declare(strict_types=1);

namespace EDT\JsonApi\Schema;

use Exception;

/**
 * @phpstan-type JsonApiRelationship = array{type: non-empty-string, id: non-empty-string}
 * @phpstan-type JsonApiRelationships = array<non-empty-string, array{data: list<JsonApiRelationship>|JsonApiRelationship|null}>
 */
class RelationshipObject
{
    private ResourceLinkageInterface $data;

    private function __construct(ResourceLinkageInterface $data)
    {
        $this->data = $data;
    }

    /**
     * This constructor function takes the format defined by the JSON:API specification
     * which can be send by a client in a request and creates a Relationship Object
     * (also defined by the JSON:API specification) from it.
     *
     * The type of relationship object depends heavily on the format in the given array parameter.
     * The array given must always contain at least a field 'data' with one of the following as its
     * value:
     * * null to create an empty to-one relationship
     * * another array with the fields 'id' and 'type' to create a non-empty to-one relationship
     * * an empty array to create an empty to-many relationship
     * * an array containing more arrays with the fields 'id' and 'type' to create a non-empty
     * to-many relationship
     *
     * @param array{data: list<JsonApiRelationship>|JsonApiRelationship|null} $relationshipObject
     *
     * @throws Exception
     *
     * @see https://jsonapi.org/format/#document-resource-object-relationships
     */
    public static function createWithDataRequired(array $relationshipObject): self
    {
        $relationshipResourceLinkage = $relationshipObject[ContentField::DATA];
        if (null === $relationshipResourceLinkage || !array_is_list($relationshipResourceLinkage)) {
            $resourceLinkage = ToOneResourceLinkage::createFromArray($relationshipResourceLinkage);
        } else {
            $resourceLinkage = ToManyResourceLinkage::createFromArray($relationshipResourceLinkage);
        }

        return new self($resourceLinkage);
    }

    /**
     * @param non-empty-string $id
     * @param non-empty-string $type
     */
    public static function createToOne(string $id, string $type): self
    {
        return new self(ToOneResourceLinkage::createFromArray([
            'id'   => $id,
            'type' => $type,
        ]));
    }

    public static function createEmptyToOne(): self
    {
        return new self(ToOneResourceLinkage::createFromArray(null));
    }

    /**
     * @param list<JsonApiRelationship> $relationships
     *
     * @throws Exception
     */
    public static function createToMany(array $relationships): self
    {
        return new self(ToManyResourceLinkage::createFromArray($relationships));
    }

    /**
     * @throws Exception
     */
    public static function createEmptyToMany(): self
    {
        return new self(ToManyResourceLinkage::createFromArray([]));
    }

    public function getData(): ResourceLinkageInterface
    {
        return $this->data;
    }
}
