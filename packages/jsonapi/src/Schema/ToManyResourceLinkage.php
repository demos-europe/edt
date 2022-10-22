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
     * @var list<ResourceIdentifierObject>
     */
    protected array $resourceIdentifierObjects;

    /**
     * ToManyResourceLinkage constructor. You may want to use the static factory functions instead.
     *
     * @param list<array{type: non-empty-string, id: non-empty-string}> $content
     *
     * @throws Exception
     */
    private function __construct(array $content)
    {
        $this->resourceIdentifierObjects = array_map(
            static fn (array $resourceIdentifierObject) => new ResourceIdentifierObject($resourceIdentifierObject),
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
     * @return list<ResourceIdentifierObject> may be empty
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
     * @return list<string>
     */
    public function getIds(): array
    {
        return array_map(
            static fn (ResourceIdentifierObject $resourceIdentifierObject) => $resourceIdentifierObject->getId(),
            $this->getResourceIdentifierObjects()
        );
    }
}
