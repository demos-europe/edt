<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\RequestHandling\Body\CreationRequestBody;
use EDT\JsonApi\ResourceTypes\CreatableTypeInterface;

/**
 * @template TEntity of object
 */
class BeforeCreationEvent
{
    use ModifyEventTrait;

    /**
     * @param CreatableTypeInterface<TEntity> $type
     */
    public function __construct(
        protected readonly CreatableTypeInterface $type,
        protected readonly CreationRequestBody $requestBody
    ) {}

    /**
     * @return CreatableTypeInterface<TEntity>
     */
    public function getType(): CreatableTypeInterface
    {
        return $this->type;
    }

    public function getRequestBody(): CreationRequestBody
    {
        return $this->requestBody;
    }
}
