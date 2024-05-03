<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputHandling;

use Symfony\Component\HttpFoundation\Response;

trait EmptyResponseTrait
{
    public function createEmptyResponse(): Response
    {
        return new Response(null, Response::HTTP_NO_CONTENT, []);
    }
}
