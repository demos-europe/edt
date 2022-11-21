<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\Properties\ConfigCollection;

/**
 * @template TCondition of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @template TSorting of \EDT\Querying\Contracts\SortMethodInterface
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

    public function getInitializedConfiguration(): ConfigCollection
    {
        if (null === $this->initializedConfiguration) {
            $this->initializedConfiguration = parent::getInitializedConfiguration();
        }

        return $this->initializedConfiguration;
    }
}
