<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities\Types;

class NonClassOrInterfaceType implements TypeInterface
{
    /**
     * @param non-empty-string $rawString
     */
    public function __construct(
        protected readonly string $rawString
    ) {}

    /**
     * @param non-empty-string $rawString
     */
    public static function fromRawString(string $rawString): self
    {
        return new self($rawString);
    }

    public function getFullString(bool $withSimpleClassNames): string
    {
        return $this->rawString;
    }

    /**
     * FIXME: currently always returns an empty list, thus does not fully support: array, iterable, callable and tuple, as those may contain classes or interfaces
     */
    public function getAllFullyQualifiedNames(): array
    {
        return [];
    }

    public function getFullyQualifiedName(): ?string
    {
        return null;
    }

    public function getTemplateParameter(int $index): TypeInterface
    {
        // TODO
        throw new \InvalidArgumentException('Support for template parameters of non-classes and non-interfaces was not implemented yet.');
    }
}
