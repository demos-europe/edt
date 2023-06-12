<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use Throwable;

class DeletionFailedException extends RequestException
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $typeIdentifier
     */
    public function __construct(
        protected readonly string $id,
        protected readonly string $typeIdentifier,
        Throwable $previous
    ) {
        parent::__construct("Failed to remove `$typeIdentifier` resource with ID `$id`.", 0, $previous);
    }

    /**
     * @return non-empty-string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return non-empty-string
     */
    public function getTypeIdentifier(): string
    {
        return $this->typeIdentifier;
    }
}
