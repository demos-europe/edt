<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyPaths;

use ArrayIterator;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use IteratorAggregate;
use Traversable;

/**
 * @template-implements IteratorAggregate<int, non-empty-string>
 */
class PropertyPath implements IteratorAggregate, PropertyPathAccessInterface
{
    /**
     * @var ArrayIterator<int, non-empty-string>|null
     */
    private ?ArrayIterator $iterator = null;

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
     * @var non-empty-list<non-empty-string>
     */
    private array $path;

    /**
     * @param class-string|null $context
     * @param non-empty-list<non-empty-string>|PropertyPathInterface $path
     *
     * @throws PathException
     */
    public function __construct(?string $context, string $salt, int $accessDepth, $path)
    {
        $this->context = $context;
        $this->accessDepth = $accessDepth;
        $this->path = $path instanceof PropertyPathInterface ? $path->getAsNames() : $path;
        $this->salt = $salt;
    }

    /**
     * @return Traversable<int, non-empty-string>
     */
    public function getIterator(): Traversable
    {
        if (null === $this->iterator) {
            $this->iterator = new ArrayIterator($this->path);
        }

        return $this->iterator;
    }

    public function getAccessDepth(): int
    {
        return $this->accessDepth;
    }

    public function setPath(array $path): void
    {
        $this->path = $path;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function __toString(): string
    {
        $pathString = implode('.', $this->path);
        return "$pathString($this->accessDepth)";
    }

    /**
     * @param non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return list<PropertyPathAccessInterface>
     * @throws PathException
     */
    public static function createIndexSaltedPaths(int $count, int $depth, $properties): array
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
        return $this->path;
    }

    public function getAsNamesInDotNotation(): string
    {
        return implode('.', $this->getAsNames());
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function getCount(): int
    {
        return count($this->path);
    }
}
