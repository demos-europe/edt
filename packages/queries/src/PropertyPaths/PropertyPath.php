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
 * @template-implements IteratorAggregate<int, non-empty-string>
 */
class PropertyPath implements IteratorAggregate, PropertyPathAccessInterface
{
    /**
     * @var ArrayIterator<int, non-empty-string>
     */
    private ArrayIterator $properties;

    /**
     * @see PropertyPathAccessInterface::getAccessDepth()
     */
    private int $accessDepth;

    private string $salt;

    /**
     * @var class-string|null
     */
    private ?string $context;

    /**
     * @param class-string|null                $context
     * @param non-empty-list<non-empty-string> $properties
     *
     * @throws PathException
     */
    public function __construct(?string $context, string $salt, int $accessDepth, array $properties)
    {
        $this->context = $context;
        $this->accessDepth = $accessDepth;
        $this->setPath($properties);
        $this->salt = $salt;
    }

    /**
     * @return Traversable<int, non-empty-string>
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
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return list<PropertyPathAccessInterface>
     * @throws PathException
     */
    public static function createIndexSaltedPaths(int $count, int $depth, array $properties): array
    {
        return array_map(
            static fn (int $pathIndex): PropertyPathAccessInterface => new PropertyPath(
                null,
                (string)$pathIndex,
                $depth,
                $properties
            ),
            range(0, $count - 1));
    }

    public function getAsNames(): array
    {
        return $this->properties->getArrayCopy();
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
