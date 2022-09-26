<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

/**
 * @template I
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface FilterParserInterface
{
    /**
     * @param I $filter
     *
     * @return list<C>
     *
     * @throws FilterException
     */
    public function parseFilter($filter): array;
}
