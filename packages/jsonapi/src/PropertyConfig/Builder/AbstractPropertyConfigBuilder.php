<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\ContentField;
use Webmozart\Assert\Assert;

/**
 * Supports common tasks to set up a specific property for accesses via the generic JSON:API implementation.
 *
 * * {@link AbstractPropertyConfigBuilder::filterable filtering via property values}
 * * {@link AbstractPropertyConfigBuilder::sortable sorting via property values}
 *
 * You can also mark the property as an alias by setting {@link AbstractPropertyConfigBuilder::aliasedPath()}.
 * This will result in all accesses that aren't using custom logic using the provided path
 * and thus expecting that for the given path segments corresponding properties in the backend
 * entity exist.
 *
 * @template TEntity of object
 * @template TCondition of PathsBasedInterface
 * @template TValue
 * @template TConstructorBehaviorFactory of object
 * @template TPostConstructorBehaviorFactory of object
 * @template TUpdateBehaviorFactory of object
 *
 * @template-implements AttributeOrRelationshipBuilderInterface<TEntity, TCondition, TValue, TConstructorBehaviorFactory, TPostConstructorBehaviorFactory, TUpdateBehaviorFactory>
 */
abstract class AbstractPropertyConfigBuilder implements AttributeOrRelationshipBuilderInterface
{
    use AliasTrait;
    use FilterTrait;
    use SortTrait;

    /**
     * @var list<TConstructorBehaviorFactory>
     */
    protected array $constructorBehaviorFactories = [];

    /**
     * @var list<TPostConstructorBehaviorFactory>
     */
    protected array $postConstructorBehaviorFactories = [];

    /**
     * @var list<TUpdateBehaviorFactory>
     */
    protected array $updateBehaviorFactories = [];

    /**
     * @param non-empty-string $name
     *
     * @throws PathException
     */
    public function __construct(
        protected readonly string $name
    ) {
        Assert::notSame($this->name, ContentField::ID);
        Assert::notSame($this->name, ContentField::TYPE);
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    protected function getPropertyPath(): array
    {
        return $this->aliasedPath ?? [$this->name];
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function removeAllCreationBehaviors(): self
    {
        $this->constructorBehaviorFactories = [];
        $this->postConstructorBehaviorFactories = [];

        return $this;
    }

    public function removeAllUpdateBehaviors(): self
    {
        $this->updateBehaviorFactories = [];

        return $this;
    }

    public function addUpdateBehavior(object $behaviorFactory): self
    {
        $this->updateBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    public function addPostConstructorBehavior(object $behaviorFactory): self
    {
        return $this->addCreationBehavior($behaviorFactory);
    }

    public function addCreationBehavior(object $behaviorFactory): self
    {
        $this->postConstructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    public function addConstructorBehavior(object $behaviorFactory): self
    {
        $this->constructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }
}
