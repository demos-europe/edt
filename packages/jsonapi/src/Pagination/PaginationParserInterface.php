<?php

declare(strict_types=1);

namespace EDT\JsonApi\Pagination;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @template TPagination of object
 */
interface PaginationParserInterface
{
    /**
     * @return TPagination|null
     */
    public function getPagination(ParameterBag $urlParameters): ?object;
}
