<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierPostConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierReadabilityInterface;

/**
 * @template TEntity of object
 */
interface IdentifierConfigInterface
{
    /**
     * @return list<IdentifierPostConstructorBehaviorInterface<TEntity>>
     */
    public function getPostConstructorBehaviors(): array;

    /**
     * @return list<ConstructorBehaviorInterface>
     */
    public function getConstructorBehaviors(): array;

    /**
     * @return IdentifierReadabilityInterface<TEntity>
     */
    public function getReadability(): IdentifierReadabilityInterface;

    public function getFilterLink(): ?PropertyLinkInterface;

    public function getSortLink(): ?PropertyLinkInterface;
}
