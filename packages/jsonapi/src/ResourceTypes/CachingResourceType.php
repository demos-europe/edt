<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends AbstractResourceType<TCondition, TSorting, TEntity>
 */
abstract class CachingResourceType extends AbstractResourceType
{
    /**
     * @var array<non-empty-string, PropertyBuilder<TEntity, mixed, TCondition, TSorting>>|null
     */
    private ?array $properties = null;

    protected function getInitializedProperties(): array
    {
        if (null === $this->properties) {
            $this->properties = parent::getInitializedProperties();
        }

        return $this->properties;
    }
}
