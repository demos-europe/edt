<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\Properties\ConfigCollection;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\SortMethodInterface;

/**
 * @template TCondition of FunctionInterface<bool>
 * @template TSorting of SortMethodInterface
 * @template TEntity of object
 *
 * @template-extends AbstractResourceType<TCondition, TSorting, TEntity>
 */
abstract class CachingResourceType extends AbstractResourceType
{
    /**
     * @var ConfigCollection<TCondition, TSorting, TEntity>|null
     */
    private ?ConfigCollection $initializedConfiguration = null;

    protected function getInitializedConfiguration(): ConfigCollection
    {
        if (null === $this->initializedConfiguration) {
            $this->initializedConfiguration = parent::getInitializedConfiguration();
        }

        return $this->initializedConfiguration;
    }
}
