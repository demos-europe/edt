<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\JsonApi\Properties\TypedPathConfigCollection;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\PropertyPaths\PropertyPath;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
use Psr\Log\LoggerInterface;
use Tests\data\EmptyEntity;

class EmptyType extends AbstractResourceType
{
    public function __construct(
        private readonly ConditionFactoryInterface $conditionFactory
    ) {}

    protected function configureProperties(TypedPathConfigCollection $configCollection): void
    {
        $configCollection->configureAttribute(
            new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, ['id'])
        )->enableReadability(true);
    }

    public function getEntityClass(): string
    {
        return EmptyEntity::class;
    }

    public function isExposedAsPrimaryResource(): bool
    {
        return false;
    }

    public function getIdentifierPropertyPath(): array
    {
        return ['id'];
    }

    public function getIdentifier(): string
    {
        return 'Foobar';
    }

    public function getAccessCondition(): PathsBasedInterface
    {
        return $this->conditionFactory->false();
    }

    public function getInternalProperties(): array
    {
        return [];
    }

    public function getDefaultSortMethods(): array
    {
        return [];
    }
}
