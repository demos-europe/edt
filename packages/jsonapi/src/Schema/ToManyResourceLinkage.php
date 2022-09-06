<?php

declare(strict_types=1);

namespace EDT\JsonApi\Schema;

use Exception;
use InvalidArgumentException;
use function is_array;

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
     * @param array<int, array{type: string, id: string}> $content
     *
     * @throws Exception
     */
    private function __construct(array $content)
    {
        $this->resourceIdentifierObjects = array_map(
            static function ($resourceIdentifierObject) {
                // TODO: validate request via json-schema/symfony-validation/... to avoid manual code checks with unclear exception messages
                if (!is_array($resourceIdentifierObject)) {
                    throw new InvalidArgumentException('resourceIdentifierObjects must be provided as array');
                }

                return new ResourceIdentifierObject($resourceIdentifierObject);
            },
            $content
        );
    }

    /**
     * @param array<int, array{type: string, id: string}> $resourceLinkage
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
