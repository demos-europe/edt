<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities\Types;

use EDT\Parsing\Utilities\TypeResolver;
use InvalidArgumentException;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;

class LazyType implements TypeInterface
{
    /**
     * @var ClassOrInterfaceType|NonClassOrInterfaceType|null
     */
    protected ?TypeInterface $type = null;

    /**
     * @param non-empty-string $typeString
     */
    public function __construct(
        protected readonly string $typeString,
        protected readonly TypeResolver $typeResolver
    ) {}

    public function getFullString(bool $withSimpleClassNames): string
    {
        return $this->getType()->getFullString($withSimpleClassNames);
    }

    public function getAllFullyQualifiedNames(): array
    {
        return $this->getType()->getAllFullyQualifiedNames();
    }

    protected function getType(): ClassOrInterfaceType|NonClassOrInterfaceType
    {
        if (null === $this->type) {
            $this->type = $this->determineType();
        }

        return $this->type;
    }

    protected function determineType(): ClassOrInterfaceType|NonClassOrInterfaceType
    {
        try {
            $type = $this->typeResolver->getResolvedType($this->typeString);
            if ($type instanceof Object_ || $type instanceof Collection) {
                return ClassOrInterfaceType::fromType($type, $this->typeResolver);
            }
        } catch (InvalidArgumentException $exception) {
            // @ignoreException
        }

        return NonClassOrInterfaceType::fromRawString($this->typeString);
    }

    public function getFullyQualifiedName(): ?string
    {
        return $this->getType()->getFullyQualifiedName();
    }

    public function getTemplateParameter(int $index): TypeInterface
    {
        return $this->getType()->getTemplateParameter($index);
    }
}
