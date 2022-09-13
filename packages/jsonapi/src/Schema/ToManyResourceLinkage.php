<?php

declare(strict_types=1);

namespace EDT\JsonApi\Schema;

use Exception;

/**
 * A JSON API Resource Linkage with a to-many cardinality.
 */
class ToManyResourceLinkage implements ResourceLinkageInterface
{
    /**
     * @var array<int, ResourceIdentifierObject>
     */
    protected $resourceIdentifierObjects;

    /**
     * ToManyResourceLinkage constructor. You may want to use the static factory functions instead.
     *
     * @param array<int, array{type: non-empty-string, id: non-empty-string}> $content
     *
     * @throws Exception
     */
    private function __construct(array $content)
    {
        $this->resourceIdentifierObjects = array_map(
            static function (array $resourceIdentifierObject) {
                return new ResourceIdentifierObject($resourceIdentifierObject);
            },
            $content
        );
    }

    /**
     * @param list<array{type: non-empty-string, id: non-empty-string}> $resourceLinkage
     *
     * @throws Exception
     *
     * @see https://jsonapi.org/format/#document-resource-object-linkage
     */
    public static function createFromArray(array $resourceLinkage): self
    {
        return new self($resourceLinkage);
    }

    /**
     * @return array<int, ResourceIdentifierObject> may be empty
     */
    public function getResourceIdentifierObjects(): array
    {
        return $this->resourceIdentifierObjects;
    }

    public function getCardinality(): Cardinality
    {
        return Cardinality::getToMany();
    }

    public function containsSpecificTypeOnly(string $type): bool
    {
        foreach ($this->getResourceIdentifierObjects() as $resourceIdentifierObject) {
            if ($type !== $resourceIdentifierObject->getType()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function getIds(): array
    {
        return array_map(static function (ResourceIdentifierObject $resourceIdentifierObject) {
            return $resourceIdentifierObject->getId();
        }, $this->getResourceIdentifierObjects());
    }
}
