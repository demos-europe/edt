<?php

declare(strict_types=1);

namespace EDT\Querying\Pagination;

class PagePagination
{
    /**
     * @var positive-int
     */
    private int $size;

    /**
     * @var positive-int
     */
    private int $number;

    /**
     * @param positive-int $size
     * @param positive-int $number
     */
    public function __construct(int $size, int $number)
    {
        $this->size = $size;
        $this->number = $number;
    }

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
