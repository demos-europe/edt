<?php

declare(strict_types=1);

namespace EDT\Querying\Pagination;

class OffsetPagination
{
    /**
     * @var int<0, max>
     */
    private int $offset;

    /**
     * @var positive-int
     */
    private int $limit;

    /**
     * @param int<0, max>  $offset
     * @param positive-int $limit
     */
    public function __construct(int $offset, int $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

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
