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
 * @template-implements IteratorAggregate<int,non-empty-string>
 */
class PropertyPath implements IteratorAggregate, PropertyPathAccessInterface
{
    /**
     * @var ArrayIterator<int,non-empty-string>
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
     * @var class-string|null
     */
    private $context;

    /**
     * @param class-string|null $context
     * @param non-empty-string  $property
     * @param non-empty-string  ...$properties
     * @throws PathException
     */
    public function __construct(?string $context, string $salt, int $accessDepth, string $property, string ...$properties)
    {
        array_unshift($properties, $property);

        $this->context = $context;
        $this->accessDepth = $accessDepth;
        $this->setPath(array_values($properties));
        $this->salt = $salt;
    }

    /**
     * @return Traversable<int,non-empty-string>
     */
    public function getIterator(): Traversable
    {
        return $this->properties;
    }

    public function getAccessDepth(): int
    {
        return $this->accessDepth;
    }

    public function setPath(array $path): void
    {
        if (in_array('', $path, true)) {
            throw PathException::emptyPart(...$path);
        }
        $this->properties = new ArrayIterator($path);
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function __toString(): string
    {
        $pathString = implode('.', $this->properties->getArrayCopy());
        return "$pathString($this->accessDepth)";
    }

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return list<PropertyPathAccessInterface>
     * @throws PathException
     */
    public static function createIndexSaltedPaths(int $count, int $depth, string $property, string ...$properties): array
    {
        return array_map(static function (int $pathIndex) use ($depth, $property, $properties): PropertyPathAccessInterface {
            return new PropertyPath(null, (string)$pathIndex, $depth, $property, ...$properties);
        }, range(0, $count - 1));
    }

    public function getAsNames(): array
    {
        return array_values(Iterables::asArray($this));
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
