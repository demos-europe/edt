<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Utilities\Iterables;
use EDT\Wrapping\WrapperFactories\WrapperObject;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

/**
 * Implementation of {@link IncludeDefinitionInterface} providing mostly hardcoded
 * behavior tailored for {@link ResourceTypeInterface} implementations.
 *
 * @template TEntity of object
 * @template TValue of object
 * @template-implements IncludeDefinitionInterface<TEntity, TValue>
 */
class IncludeDefinition implements IncludeDefinitionInterface
{
    /**
     * @var PropertyDefinitionInterface<TEntity, WrapperObject<TValue>|list<WrapperObject<TValue>>|null>
     */
    private PropertyDefinitionInterface $propertyDefinition;

    /**
     * @var ResourceTypeInterface<PathsBasedInterface, PathsBasedInterface, TValue>
     */
    private ResourceTypeInterface $targetType;

    /**
     * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
     * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
     *
     * @param PropertyDefinitionInterface<TEntity, WrapperObject<TValue>|list<WrapperObject<TValue>>|null> $propertyDefinition
     * @param ResourceTypeInterface<TCondition, TSorting, TValue>                               $targetType
     */
    public function __construct(
        PropertyDefinitionInterface $propertyDefinition,
        ResourceTypeInterface $targetType
    ) {
        $this->propertyDefinition = $propertyDefinition;
        $this->targetType = $targetType;
    }

    public function determineData(object $entity, ParamBag $params)
    {
        $data = $this->propertyDefinition->determineData($entity, $params);

        if (null === $data) {
            return null;
        }

        /**
         * Because the next transformer may require the actual entity instance instead
         * of the wrapper we need to manually unwrap it here. The alternative would be
         * to either adjust all parameter types in the transformers to accept {@link WrapperObject}
         * or to dynamically extend {@link WrapperObject} from the current entity class via eval().
         */
        return is_iterable($data)
            ? array_map(
                static fn (WrapperObject $object): object => $object->getObject(),
                array_values(Iterables::asArray($data))
            )
            : $data->getObject();
    }

    public function getResourceKey(): string
    {
        return $this->targetType::getName();
    }

    public function getTransformer(): TransformerAbstract
    {
        return $this->targetType->getTransformer();
    }

    public function isToMany($propertyData): bool
    {
        return is_iterable($propertyData);
    }

    public function isToBeUsedAsDefaultField(): bool
    {
        return $this->propertyDefinition->isToBeUsedAsDefaultField();
    }

    public function isToBeUsedAsDefaultInclude(): bool
    {
        // TODO: compare JSON:API specification with implementation to check how to handle default includes correctly
        return $this->isToBeUsedAsDefaultField();
    }
}
