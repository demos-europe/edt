<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyPaths;

use ArrayIterator;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use IteratorAggregate;
use Traversable;
use function is_string;

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
     * @var non-empty-list<non-empty-string>
     */
    private array $path;

    /**
     * @param class-string|null $context
     * @param int $accessDepth {@link PropertyPathAccessInterface::getAccessDepth()}
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $path
     *
     * @throws PathException
     */
    public function __construct(
        private readonly ?string $context,
        private readonly string $salt,
        private readonly int $accessDepth,
        string|array|PropertyPathInterface $path
    ) {
        if (is_string($path)) {
            $path = [$path];
        }
        $this->path = $path instanceof PropertyPathInterface ? $path->getAsNames() : $path;
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
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return list<PropertyPathAccessInterface>
     * @throws PathException
     */
    public static function createIndexSaltedPaths(int $count, int $depth, string|array|PropertyPathInterface $properties): array
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
