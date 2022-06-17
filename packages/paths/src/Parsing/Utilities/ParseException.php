<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use Exception;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;

class ParseException extends Exception
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @param class-string $class
     */
    public static function docblockParsingFailed(string $class, Exception $cause): self
    {
        $self = new self("Failed to parse docblock of class '$class'", 0, $cause);
        $self->className = $class;

        return $self;
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}
