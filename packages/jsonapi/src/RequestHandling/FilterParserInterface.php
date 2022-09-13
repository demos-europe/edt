<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

/**
 * @template I
 * @template C of \EDT\Querying\Contracts\FunctionInterface<bool>
 */
interface FilterParserInterface
{
    /**
     * @param I $filter
     *
     * @return array<int, C>
     *
     * @throws FilterException
     */
    public function parseFilter($filter): array;
}
