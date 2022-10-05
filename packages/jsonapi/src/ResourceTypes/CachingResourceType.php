<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

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
     * @var array<non-empty-string,non-empty-list<non-empty-string>>|null
     */
    protected $aliasesCache;
    /**
     * @var array<non-empty-string, non-empty-string|null>|null
     */
    protected $filterablePropertiesCache;
    /**
     * @var array<non-empty-string, non-empty-string|null>|null
     */
    protected $readablePropertiesCache;
    /**
     * @var array<non-empty-string, non-empty-string|null>|null
     */
    protected $sortablePropertiesCache;

    public function getReadableProperties(): array
    {
        if (null === $this->readablePropertiesCache) {
            $this->readablePropertiesCache = parent::getReadableProperties();
        }

        return $this->readablePropertiesCache;
    }

    public function getFilterableProperties(): array
    {
        if (null === $this->filterablePropertiesCache) {
            $this->filterablePropertiesCache = parent::getFilterableProperties();
        }

        return $this->filterablePropertiesCache;
    }

    public function getSortableProperties(): array
    {
        if (null === $this->sortablePropertiesCache) {
            $this->sortablePropertiesCache = parent::getSortableProperties();
        }

        return $this->sortablePropertiesCache;
    }


    public function getAliases(): array
    {
        if (null === $this->aliasesCache) {
            $this->aliasesCache = parent::getAliases();
        }

        return $this->aliasesCache;
    }
}
