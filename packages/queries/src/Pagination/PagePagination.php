<?php

declare(strict_types=1);

namespace EDT\Querying\Pagination;

class PagePagination
{
    /**
     * @param positive-int $size
     * @param positive-int $number
     */
    public function __construct(
        private readonly int $size,
        private readonly int $number
    ) {}

    /**
     * @return positive-int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return positive-int
     */
    public function getSize(): int
    {
        return $this->size;
    }
}
