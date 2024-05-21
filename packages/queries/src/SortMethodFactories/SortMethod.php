<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethodFactories;

use EDT\Querying\Contracts\PathAdjustableInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Querying\Utilities\PathConverterTrait;

class SortMethod implements SortMethodInterface
{
    use PathConverterTrait;

    /**
     * @var non-empty-list<non-empty-string>
     */
    protected array $path;

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $path
     *
     * @internal
     */
    public function __construct(array|string|PropertyPathInterface $path, protected readonly bool $descending)
    {
        $this->path = self::pathToArray($path);
    }

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     */
    public static function ascendingByPath(array|string|PropertyPathInterface $properties): self
    {
        return new self($properties, false);
    }

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     */
    public static function descendingByPath(array|string|PropertyPathInterface $properties): self
    {
        return new self($properties, true);
    }

    public function adjustPath(callable $callable): void
    {
        $this->path = $callable($this->path);
    }

    public function getAsString(): string
    {
        $pathString = self::pathToString($this->path);
        return $this->descending ? "-$pathString" : $pathString;
    }
}
