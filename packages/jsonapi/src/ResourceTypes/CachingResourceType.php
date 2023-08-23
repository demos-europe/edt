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
     * @var EntityConfig<TCondition, TSorting, TEntity>|null
     */
    private ?EntityConfig $entityConfig = null;

    protected function getInitializedProperties(): EntityConfig
    {
        if (null === $this->entityConfig) {
            $this->entityConfig = parent::getInitializedProperties();
        }

        return $this->entityConfig;
    }
}
