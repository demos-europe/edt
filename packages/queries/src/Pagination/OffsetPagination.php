<?php

declare(strict_types=1);

namespace EDT\Querying\Pagination;

class OffsetPagination
{
    /**
     * @param int<0, max>  $offset
     * @param positive-int $limit
     */
    public function __construct(
        private readonly int $offset,
        private readonly int $limit
    ) {}

    /**
     * @return int<0, max>
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return positive-int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }
}
