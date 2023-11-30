<?php

declare(strict_types=1);

namespace Tests\Utilities\subnamespace;

use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;

/**
 * This class may be handled in a special way, because it contains two template parameters and my be confused with a {@link Collection} instead of an {@link Object_}.
 *
 * @template T1
 * @template T2
 */
class DoubleTemplateClassForProperty
{

}
