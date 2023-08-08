<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\JsonApi\RequestHandling\Body\RequestBody;
use EDT\JsonApi\Requests\PropertyUpdaterTrait;
use EDT\Querying\Contracts\PathsBasedInterface;

class EntityUpdater
{
    use PropertyUpdaterTrait;

    /**
     * @template TEntity of object
     *
     * @param TEntity $entity
     * @param SetabilityCollection<PathsBasedInterface, PathsBasedInterface, TEntity> $setabilities
     *
     * @return array{0: list<bool>, 1: list<bool>, 2: list<bool>} if setting properties (attributes, to-one- and to-many-relationships) had side effects
     */
    public function updateEntity(
        object $entity,
        RequestBody $requestBody,
        SetabilityCollection $setabilities
    ): array {
        return [
            $this->updateAttributes($entity, $setabilities->getAttributeSetabilities(), $requestBody->getAttributes()),
            $this->updateToOneRelationships($entity, $setabilities->getToOneRelationshipSetabilities(), $requestBody->getToOneRelationships()),
            $this->updateToManyRelationships($entity, $setabilities->getToManyRelationshipSetabilities(), $requestBody->getToManyRelationships()),
        ];
    }
}
