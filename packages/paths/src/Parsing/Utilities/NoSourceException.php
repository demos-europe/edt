<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use Exception;

/**
 * In some cases no source code can be retrieved for the given type (e.g.
 * {@link IteratorAggregate}). If you assume these cases not relevant for your
 * use case, you can ignore this exception.
 */
class NoSourceException extends Exception
{

}
