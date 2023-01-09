<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TFilter
 * @template TCondition of PathsBasedInterface
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
