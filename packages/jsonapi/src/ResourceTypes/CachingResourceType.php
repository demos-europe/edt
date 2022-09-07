<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

/**
 * @template T of object
 *
 * @template-extends AbstractResourceType<T>
 */
abstract class CachingResourceType extends AbstractResourceType
{
    /**
     * @var array<string,non-empty-array<int,string>>|null
     */
    protected $aliasesCache;
    /**
     * @var array<string,string|null>|null
     */
    protected $filterablePropertiesCache;
    /**
     * @var array<string,string|null>|null
     */
    protected $readablePropertiesCache;
    /**
     * @var array<string,string|null>|null
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
