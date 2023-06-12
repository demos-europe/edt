<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\JsonApi\ResourceTypes\PropertyBuilderFactory;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\PropertyPaths\PropertyPath;
use EDT\Wrapping\Contracts\Types\ReindexableTypeInterface;
use Psr\Log\LoggerInterface;
use Tests\data\EmptyEntity;

class EmptyType extends AbstractResourceType
{
    public function __construct(
        protected readonly ConditionFactoryInterface $conditionFactory,
        protected readonly PropertyBuilderFactory $propertyBuilderFactory,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver
    ) {}

    public function getEntityClass(): string
    {
        return EmptyEntity::class;
    }

    public function getTypeName(): string
    {
        return 'Foobar';
    }

    public function getAccessCondition(): PathsBasedInterface
    {
        return $this->conditionFactory->false();
    }

    public function getDefaultSortMethods(): array
    {
        return [];
    }

    protected function getProperties(): array
    {
        return [
            $this->createAttribute(
                new PropertyPath(null, '', PropertyPath::UNPACK, ['id'])
            )->readable()
        ];
    }

    protected function getPropertyBuilderFactory(): PropertyBuilderFactory
    {
        return $this->propertyBuilderFactory;
    }

    public function getEntityByIdentifier(string $identifier, array $conditions): object
    {
        throw new \RuntimeException();
    }

    public function getEntitiesByIdentifiers(array $identifiers, array $conditions, array $sortMethods): array
    {
        throw new \RuntimeException();
    }

    protected function getMessageFormatter(): MessageFormatter
    {
        throw new \RuntimeException();
    }

    protected function getLogger(): LoggerInterface
    {
        throw new \RuntimeException();
    }

    public function assertMatchingEntities(array $entities, array $conditions): void
    {
        throw new \RuntimeException();
    }

    public function assertMatchingEntity(object $entity, array $conditions): void
    {
        throw new \RuntimeException();
    }

    public function isMatchingEntity(object $entity, array $conditions): bool
    {
        throw new \RuntimeException();
    }

    protected function getReindexableType(): ReindexableTypeInterface
    {
        throw new \RuntimeException();
    }
}
