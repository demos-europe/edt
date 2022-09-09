<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

/**
 * @template I
 * @template F of \EDT\Querying\Contracts\FunctionInterface<bool>
 */
interface FilterParserInterface
{
    /**
     * @param I $filter
     *
     * @return array<int, F>
     *
     * @throws FilterException
     */
    public function parseFilter($filter): array;
}
