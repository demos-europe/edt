<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\JsonApi\InputHandling\RepositoryInterface;
use EDT\JsonApi\ResourceTypes\PassThroughType;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\JsonApi\Utilities\PropertyBuilderFactory;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\Contracts\ResourceTypeProviderInterface;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Webmozart\Assert\Assert;
use function is_array;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends MagicResourceConfigBuilder<TCondition, TSorting, TEntity>
 * @template-implements ResourceTypeProviderInterface<TCondition, TSorting, TEntity>
 */
class TypeConfig extends MagicResourceConfigBuilder implements ResourceTypeProviderInterface
{
    /**
     * @var null|ResourceTypeInterface<TCondition, TSorting, TEntity>
     */
    protected ?ResourceTypeInterface $correspondingType = null;

    /**
     * @var list<TCondition>
     */
    protected array $accessConditions = [];

    /**
     * @var non-empty-string|null
     */
    protected ?string $typeName = null;

    /**
     * @var list<TSorting>
     */
    protected array $defaultSortMethods = [];

    /**
     * @var non-empty-list<non-empty-string>
     */
    protected array $identifierPropertyPath = [ContentField::ID];

    /**
     * @param class-string<TEntity> $entityClass
     * @param PropertyBuilderFactory<TCondition, TSorting> $propertyBuilderFactory
     * @param RepositoryInterface<TCondition, TSorting, TEntity> $repository
     */
    public function __construct(
        string $entityClass,
        PropertyBuilderFactory $propertyBuilderFactory,
        protected readonly RepositoryInterface $repository,
        protected readonly SchemaPathProcessor $pathProcessor,
        protected readonly ?LoggerInterface $logger = null
    ) {
        parent::__construct($entityClass, $propertyBuilderFactory);
    }

    /**
     * @param list<TCondition> $conditions
     */
    public function setAccessConditions(array $conditions): void
    {
        $this->accessConditions = $conditions;
    }

    /**
     * @param non-empty-string $name
     */
    public function setTypeName(string $name): void
    {
        $this->typeName = $name;
    }

    /**
     * @param list<TSorting> $sortMethods
     */
    public function setDefaultSortMethods(array $sortMethods): void
    {
        $this->defaultSortMethods = $sortMethods;
    }

    /**
     * @param non-empty-list<non-empty-string> $path
     */
    public function setIdentifierPropertyPath(array|PropertyPathInterface $path): void
    {
        $this->identifierPropertyPath = is_array($path) ? $path : $path->getAsNames();
    }

    /**
     * @return non-empty-string
     */
    protected function getTypeName(): string
    {
        if (null !== $this->typeName) {
            return $this->typeName;
        }

        $shortName = (new ReflectionClass($this->entityClass))->getShortName();
        Assert::stringNotEmpty($shortName);

        return $shortName;
    }

    /**
     * Use this configuration to return a corresponding type, that can be used to process requests.
     *
     * Later changes to this config builder or its properties may or may not be reflected in previously returned config
     * instances.
     */
    public function getType(): ResourceTypeInterface
    {
        if (null === $this->correspondingType) {
            $config = $this->build();

            $this->correspondingType = new PassThroughType(
                $this->entityClass,
                $this->repository,
                $this->pathProcessor,
                $this->getTypeName(),
                $this->accessConditions,
                $this->defaultSortMethods,
                $this->identifierPropertyPath,
                $config->getFilteringProperties(),
                $config->getSortingProperties(),
                $config->getInstantiability(),
                $config->getReadability(),
                $config->getUpdatability(),
                $this->logger
            );
        }

        return $this->correspondingType;
    }
}
