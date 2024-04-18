<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

trait FilterTrait
{
    protected bool $filterable = false;

    public function filterable(): self
    {
        return $this->setFilterable();
    }

    public function setFilterable(): self
    {
        $this->filterable = true;

        return $this;
    }

    public function setNonFilterable(): self
    {
        $this->filterable = false;

        return $this;
    }
}
