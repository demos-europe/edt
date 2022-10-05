<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
use League\Fractal\ParamBag;

/**
 * Implementation of {@link PropertyDefinitionInterface} providing mostly hardcoded
 * behavior.
 *
 * @template TCondition of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @template TSorting of \EDT\Querying\Contracts\SortMethodInterface
 * @template TEntity of object
 * @template TValue
 *
 * @template-implements PropertyDefinitionInterface<TEntity, TValue>
 */
class PropertyDefinition implements PropertyDefinitionInterface
{
    /**
     * @var non-empty-string
     */
    private $propertyName;

    /**
     * @var bool
     */
    private $toBeUsedAsDefaultField;

    /**
     * @var WrapperObjectFactory
     */
    private $wrapperFactory;

    /**
     * @var ResourceTypeInterface<TCondition, TSorting, TEntity>
     */
    private $type;

    /**
     * @var null|callable(TEntity, ParamBag): TValue
     */
    private $customReadCallable;

    /**
     * @param non-empty-string                                     $propertyName
     * @param ResourceTypeInterface<TCondition, TSorting, TEntity> $type
     * @param null|callable(TEntity, ParamBag): TValue             $customReadCallable
     */
    public function __construct(
        string $propertyName,
        bool $toBeUsedAsDefaultField,
        ResourceTypeInterface $type,
        WrapperObjectFactory $wrapperFactory,
        ?callable $customReadCallable
    ) {
        $this->propertyName = $propertyName;
        $this->toBeUsedAsDefaultField = $toBeUsedAsDefaultField;
        $this->wrapperFactory = $wrapperFactory;
        $this->type = $type;
        $this->customReadCallable = $customReadCallable;
    }

    public function determineData(object $entity, ParamBag $params)
    {
        $customReadCallable = $this->customReadCallable;
        if (null !== $customReadCallable) {
            return $customReadCallable($entity, $params);
        }

        // the application will pass raw entities into the transformer,
        // we need to wrap these to prevent access beyond authorization
        $entity = $this->wrapperFactory->createWrapper($entity, $this->type);

        return $entity->getPropertyValue($this->propertyName);
    }

    public function isToBeUsedAsDefaultField(): bool
    {
        return $this->toBeUsedAsDefaultField;
    }
}
