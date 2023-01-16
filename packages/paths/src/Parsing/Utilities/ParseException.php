<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use Exception;
use Throwable;

class ParseException extends Exception
{
    /**
     * @param class-string $className
     * @param non-empty-string $message
     */
    protected function __construct(protected string $className, string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param class-string $class
     */
    public static function docblockParsingFailed(string $class, Exception $cause): self
    {
        return new self($class, "Failed to parse docblock of class '$class'", 0, $cause);
    }

    /**
     * @return class-string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}
