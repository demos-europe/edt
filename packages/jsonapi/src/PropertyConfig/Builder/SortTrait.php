<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

trait SortTrait
{
    protected bool $sortable = false;

    public function sortable(): self
    {
        return $this->setSortable();
    }

    public function setSortable(): self
    {
        $this->sortable = true;

        return $this;
    }

    public function setNonSortable(): self
    {
        $this->sortable = false;

        return $this;
    }
}
