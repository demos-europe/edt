<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorParameterInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierPostInstantiabilityInterface;

/**
 * @template TEntity of object
 */
interface IdentifierConfigInterface
{
    /**
     * @return IdentifierPostInstantiabilityInterface<TEntity>
     */
    public function getPostInstantiability(): ?IdentifierPostInstantiabilityInterface;

    public function getInstantiability(): ?ConstructorParameterInterface;

    /**
     * @return IdentifierReadabilityInterface<TEntity>
     */
    public function getReadability(): IdentifierReadabilityInterface;

    public function getFilterLink(): ?PropertyLinkInterface;

    public function getSortLink(): ?PropertyLinkInterface;
}
