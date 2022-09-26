<?php

declare(strict_types=1);

namespace EDT\Querying\Pagination;

class OffsetBasedPagination
{
    /**
     * @var int<0, max>
     */
    private $offset;

    /**
     * @var positive-int
     */
    private $limit;

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
