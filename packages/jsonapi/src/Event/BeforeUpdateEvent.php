<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\RequestHandling\Body\UpdateRequestBody;
use EDT\JsonApi\ResourceTypes\UpdatableTypeInterface;

/**
 * @template TEntity of object
 */
class BeforeUpdateEvent
{
    use ModifyEventTrait;

    /**
     * @param UpdatableTypeInterface<TEntity> $type
     */
    public function __construct(
        protected readonly UpdatableTypeInterface $type,
        protected readonly UpdateRequestBody $requestBody
    ) {}

    /**
     * @return UpdatableTypeInterface<TEntity>
     */
    public function getType(): UpdatableTypeInterface
    {
        return $this->type;
    }

    /**
     * @return non-empty-string
     */
    public function getEntityIdentifier(): string
    {
        return $this->requestBody->getId();
    }

    public function getRequestBody(): UpdateRequestBody
    {
        return $this->requestBody;
    }
}
