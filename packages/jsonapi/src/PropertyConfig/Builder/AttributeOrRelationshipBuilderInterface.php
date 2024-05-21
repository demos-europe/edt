<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\ConditionFactory\DrupalFilterInterface;
use EDT\JsonApi\ApiDocumentation\DefaultField;

/**
 * This interface defines configuration options that are available for attributes and relationships, but *not* the ID.
 *
 * @template TEntity of object
 * @template TValue the type of value corresponding to the resource property
 * @template TConstructorBehaviorFactory of object
 * @template TPostConstructorBehaviorFactory of object
 * @template TUpdateBehaviorFactory of object
 *
 * @template-extends PropertyConfigBuilderInterface<TEntity, TValue>
 */
interface AttributeOrRelationshipBuilderInterface extends PropertyConfigBuilderInterface
{
    /**
     * @param null|callable(TEntity): TValue $customReadCallback to be set if this property needs special handling when read
     *
     * @return $this
     *
     * @deprecated use {@link setReadableByPath()} or {@link setReadableByCallable()} instead
     */
    public function readable(bool $defaultField = false, callable $customReadCallback = null): self;

    public function setReadableByPath(DefaultField $defaultField = DefaultField::NO): self;

    public function setReadableByCallable(callable $behavior, DefaultField $defaultField = DefaultField::NO): self;

    /**
     * Remove the readability that was previously set on this resource property.
     *
     * @return $this
     */
    public function setNonReadable(): self;

    /**
     * Removes all update behaviors previously set on this relationship.
     *
     * @return $this
     */
    public function removeAllUpdateBehaviors(): self;

    /**
     * Add behavior to execute when this attribute is provided in an update request.
     *
     * The behavior will only be executed, if the resource property the behavior is added to is present in an update
     * request.
     *
     * To set the given value, the path to the backing entity property is used.
     * This is either simply the name of this resource property or, if an {@link setAliasedPath alias} was set, the path
     * provided on that call. How the path is handled to set the value provided in the request depends on the
     * {@link PropertyAccessorInterface} implementation configured.
     *
     * @param list<DrupalFilterInterface> $entityConditions
     *
     * @return $this
     */
    public function addPathUpdateBehavior(array $entityConditions = []): self;

    /**
     * @param TConstructorBehaviorFactory $behaviorFactory
     *
     * @return $this
     */
    public function addConstructorBehavior(object $behaviorFactory): self;

    /**
     * @param TPostConstructorBehaviorFactory $behaviorFactory
     *
     * @return $this
     */
    public function addCreationBehavior(object $behaviorFactory): self;

    /**
     * Add a behavior to execute if this resource relationship is provided in an update request.
     *
     * The behavior will only be executed, if the resource property the behavior is added to is present in an update
     * request.
     *
     * @param TUpdateBehaviorFactory $behaviorFactory
     *
     * @return $this
     */
    public function addUpdateBehavior(object $behaviorFactory): self;

    /**
     * Remove all initializable behaviors previously set on this relationship.
     *
     * @return $this
     */
    public function removeAllCreationBehaviors(): self;

    /**
     * @param TPostConstructorBehaviorFactory $behaviorFactory
     *
     * @return $this
     *
     * @deprecated use {@link addCreationBehavior} instead
     */
    public function addPostConstructorBehavior(object $behaviorFactory): self;
}
