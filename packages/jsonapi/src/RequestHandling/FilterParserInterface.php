<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

/**
 * @template TFilter
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface FilterParserInterface
{
    /**
     * @param TFilter $filter
     *
     * @return list<TCondition>
     *
     * @throws FilterException
     */
    public function parseFilter($filter): array;
}
