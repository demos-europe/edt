<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 * @template T of object
 *
 * @template-extends AbstractResourceType<C, S, T>
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
