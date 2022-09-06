<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

class ExcludeException extends TransformException
{
    public static function notAllowed(): self
    {
        return new self('excluding relationships is not supported');
    }
}
