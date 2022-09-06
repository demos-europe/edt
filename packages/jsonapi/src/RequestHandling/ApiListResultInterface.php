<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use Pagerfanta\Pagerfanta;

/**
 * @template O of object
 */
interface ApiListResultInterface
{
    /**
     * @return array<int, O>
     */
    public function getList(): array;

    public function getPaginator(): ?Pagerfanta;

    /**
     * @return array<string, mixed>
     */
    public function getMeta(): array;
}
