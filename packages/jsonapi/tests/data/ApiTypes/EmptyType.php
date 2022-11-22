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
    private WrapperObjectFactory $wrapperFactory;

    private ConditionFactoryInterface $conditionFactory;

    private LoggerInterface $logger;

    private MessageFormatter $messageFormatter;

    public function __construct(
        WrapperObjectFactory $wrapperFactory,
        ConditionFactoryInterface $conditionFactory,
        LoggerInterface $logger,
        MessageFormatter $messageFormatter
    ) {
        $this->wrapperFactory = $wrapperFactory;
        $this->conditionFactory = $conditionFactory;
        $this->logger = $logger;
        $this->messageFormatter = $messageFormatter;
    }

    protected function configureProperties(TypedPathConfigCollection $configCollection): void
    {
        $configCollection->configureAttribute(
            new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, ['id'])
        )->enableReadability(true);
    }

    protected function getWrapperFactory(): WrapperObjectFactory
    {
        return $this->wrapperFactory;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getMessageFormatter(): MessageFormatter
    {
        return $this->messageFormatter;
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
