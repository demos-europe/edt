<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use Throwable;

class TagTypeParseException extends ParseException
{
    /**
     * @param class-string $className
     */
    public function __construct(
        string $className,
        protected readonly TagWithType $tag,
        Throwable $previous
    ) {
        parent::__construct($className, "Cound not find the class for tag '{$tag->getName()}' in class '$className'.", 0, $previous);
    }

    public function getTag(): TagWithType
    {
        return $this->tag;
    }
}
