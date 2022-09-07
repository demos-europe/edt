<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Contracts\WrapperFactoryInterface;
use EDT\Wrapping\Contracts\WrapperInterface;
use League\Fractal\ParamBag;
use function in_array;

/**
 * Implementation of {@link PropertyDefinitionInterface} providing mostly hardcoded
 * behavior.
 *
 * @template O of object
 * @template R
 * @template-implements PropertyDefinitionInterface<O, R>
 */
class PropertyDefinition implements PropertyDefinitionInterface
{
    /**
     * @var string
     */
    private $propertyName;
    /**
     * @var bool
     */
    private $toBeUsedAsDefaultField;

    /**
     * @var WrapperFactoryInterface<O, WrapperInterface>
     */
    private $wrapperFactory;

    /**
     * @var ResourceTypeInterface<O>
     */
    private $type;

    /**
     * @var null|callable(O, ParamBag): R
     */
    private $customReadCallable = null;

    /**
     * @param ResourceTypeInterface<O>                     $type
     * @param WrapperFactoryInterface<O, WrapperInterface> $wrapperFactory
     * @param array<int,string>                            $defaultProperties
     * @param null|callable(O, ParamBag): R                $customReadCallable
     */
    public function __construct(
        string $propertyName,
        array $defaultProperties,
        ResourceTypeInterface $type,
        WrapperFactoryInterface $wrapperFactory,
        ?callable $customReadCallable
    ) {
        $this->propertyName = $propertyName;
        $this->toBeUsedAsDefaultField = in_array($propertyName, $defaultProperties, true);
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

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
}
