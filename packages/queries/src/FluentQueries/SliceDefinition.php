<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

class SliceDefinition
{
    /**
     * @var int<0, max>
     */
    private int $offset = 0;
    /**
     * @var positive-int|null
     */
    private ?int $limit = null;

    /**
     * @return int<0, max>
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int<0, max> $offset
     *
     * @return $this
     */
    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return positive-int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param positive-int|null $limit
     *
     * @return $this
     */
    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }
}
