<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
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
     * @var non-empty-string
     */
    private $propertyName;

    /**
     * @var bool
     */
    private $toBeUsedAsDefaultField;

    /**
     * @var WrapperFactoryInterface<PathsBasedInterface, PathsBasedInterface, O, WrapperInterface>
     */
    private $wrapperFactory;

    /**
     * @var ResourceTypeInterface<PathsBasedInterface, PathsBasedInterface, O>
     */
    private $type;

    /**
     * @var null|callable(O, ParamBag): R
     */
    private $customReadCallable = null;

    /**
     * @template C of \EDT\Querying\Contracts\PathsBasedInterface
     * @template S of \EDT\Querying\Contracts\PathsBasedInterface
     *
     * @param non-empty-string                                   $propertyName
     * @param ResourceTypeInterface<C, S, O>                     $type
     * @param WrapperFactoryInterface<C, S, O, WrapperInterface> $wrapperFactory
     * @param list<non-empty-string>                             $defaultProperties
     * @param null|callable(O, ParamBag): R                      $customReadCallable
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

    /**
     * @return non-empty-string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
}
