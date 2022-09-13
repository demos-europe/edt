<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities\TypeAccessors;

use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template-extends AbstractTypeAccessor<TypeInterface<C, object>>
 */
class InternTypeAccessor extends AbstractTypeAccessor
{
    /**
     * @var TypeProviderInterface<C>
     */
    private $typeProvider;

    /**
     * @param TypeProviderInterface<C> $typeProvider
     */
    public function __construct(TypeProviderInterface $typeProvider)
    {
        $this->typeProvider = $typeProvider;
    }

    protected function getProperties(TypeInterface $type): array
    {
        return $type->getInternalProperties();
    }

    protected function getType(string $typeIdentifier): TypeInterface
    {
        return $this->typeProvider->getType($typeIdentifier);
    }
}

