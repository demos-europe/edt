<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\JsonApi\ResourceConfig\ResourceConfig;
use EDT\JsonApi\ResourceConfig\ResourceConfigInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends MagicResourceConfigBuilder<TCondition, TSorting, TEntity>
 * @template-implements ResourceConfigBuilderInterface<TCondition, TSorting, TEntity>
 */
abstract class AbstractResourceConfigBuilder extends MagicResourceConfigBuilder implements ResourceConfigBuilderInterface
{
    public function build(): ResourceConfigInterface
    {
        return new ResourceConfig(
            $this->entityClass,
            $this->getBuiltIdentifierConfig(),
            $this->getBuiltAttributeConfigs(),
            $this->getBuiltToOneRelationshipConfigs(),
            $this->getBuiltToManyRelationshipConfigs()
        );
    }
}
