<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyPaths;

use ArrayIterator;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Utilities\Iterables;
use IteratorAggregate;
use Traversable;
use function in_array;

/**
 * @template-implements IteratorAggregate<int,string>
 */
class PropertyPath implements IteratorAggregate, PropertyPathAccessInterface
{
    /**
     * @var ArrayIterator<int,string>
     */
    private $properties;
    /**
     * @var int
     *
     * @see PropertyPathAccessInterface::getAccessDepth()
     */
    private $accessDepth;
    /**
     * @var string
     */
    private $salt;

    /**
     * @var string|null
     */
    private $context;

    /**
     * @throws PathException
     */
    public function __construct(?string $context, string $salt, int $accessDepth, string $property, string ...$properties)
    {
        $this->context = $context;
        $this->accessDepth = $accessDepth;
        $this->setPath($property, ...$properties);
        $this->salt = $salt;
    }

    /**
     * @return Traversable<int,string>
     */
    public function getIterator(): Traversable
    {
        return $this->properties;
    }

    public function getAccessDepth(): int
    {
        return $this->accessDepth;
    }

    public function setPath(string $property, string ...$properties): void
    {
        array_unshift($properties, $property);
        if (in_array('', $properties, true)) {
            throw PathException::emptyPart(...$properties);
        }
        $this->properties = new ArrayIterator($properties);
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function __toString(): string
    {
        $pathString = implode('.', $this->properties->getArrayCopy());
        return "$pathString({$this->accessDepth})";
    }

    public static function createIndexSaltedPaths(int $count, int $depth, string $property, string ...$properties): array
    {
        return array_map(static function (int $pathIndex) use ($depth, $property, $properties): PropertyPathAccessInterface {
            return new PropertyPath(null, (string)$pathIndex, $depth, $property, ...$properties);
        }, range(0, $count - 1));
    }

    public function getAsNames(): array
    {
        return Iterables::asArray($this);
    }

    public function getAsNamesInDotNotation(): string
    {
        return implode('.', $this->getAsNames());
    }

    public function getContext(): ?string
    {
        return $this->context;
    }
}
