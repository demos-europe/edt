<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use Exception;

/**
 * Indicates a problem when slicing a list of items to a requested subset.
 */
class PaginationException extends Exception
{
}
