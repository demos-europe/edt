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
 * @template O of object
 * @template T of object
 * @template-implements IncludeDefinitionInterface<O, T>
 */
class IncludeDefinition implements IncludeDefinitionInterface
{
    /**
     * @var PropertyDefinitionInterface<O, WrapperObject<T>|list<WrapperObject<T>>|null>
     */
    private $propertyDefinition;

    /**
     * @var ResourceTypeInterface<PathsBasedInterface, PathsBasedInterface, T>
     */
    private $targetType;

    /**
     * @param PropertyDefinitionInterface<O, WrapperObject<T>|list<WrapperObject<T>>|null> $propertyDefinition
     * @param ResourceTypeInterface<PathsBasedInterface, PathsBasedInterface, T>           $targetType
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
            ? array_map(static function (WrapperObject $object): object {
                return $object->getObject();
            }, array_values(Iterables::asArray($data)))
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
        return $this->isToBeUsedAsDefaultField();
    }
}
