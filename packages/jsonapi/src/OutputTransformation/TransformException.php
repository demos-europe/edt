<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use Exception;
use League\Fractal\TransformerAbstract;

/**
 * Use this exception or extending classes to imply a problem when transforming resources via
 * classes extending from {@link TransformerAbstract}.
 */
class TransformException extends Exception
{
}
