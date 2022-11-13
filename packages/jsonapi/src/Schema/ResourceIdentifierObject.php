<?php

declare(strict_types=1);

namespace EDT\JsonApi\Schema;

/**
 * Represents a Resource Identifier Object as specified by jsonapi.org version 1.1.
 */
class ResourceIdentifierObject
{
    /**
     * @var non-empty-string
     */
    private string $id;

    /**
     * @var non-empty-string
     */
    private string $type;

    /**
     * `meta` can not be validated yet, hence it is not accepted at all.
     *
     * @param array{type: non-empty-string, id: non-empty-string} $content
     */
    public function __construct(array $content)
    {
        $this->id = $content[ContentField::ID];
        $this->type = $content[ContentField::TYPE];
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
    public function getType(): string
    {
        return $this->type;
    }
}
