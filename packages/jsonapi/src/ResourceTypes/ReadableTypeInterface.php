<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use League\Fractal\TransformerAbstract;

interface ReadableTypeInterface
{
    public function getTransformer(): TransformerAbstract;
}
