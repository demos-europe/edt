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

    /**
     * @return TFilter
     *
     * @deprecated call a validator manually, that asserts that the type of $filter matches the type required by {@link self::parseFilter()}
     */
    public function validateFilter(mixed $filter);
}
