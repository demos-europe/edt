<?php

declare(strict_types=1);

namespace EDT\JsonApi\Schema;

use InvalidArgumentException;
use function array_key_exists;
use function is_string;

/**
 * Represents a Resource Identifier Object as specified by jsonapi.org version 1.1.
 */
class ResourceIdentifierObject
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @param array{type: string, id: string} $content
     */
    public function __construct(array $content)
    {
        if (!array_key_exists(ContentField::ID, $content) || !array_key_exists(ContentField::TYPE, $content)) {
            $providedKeys = implode(',', array_keys($content));
            throw new InvalidArgumentException("\$content MUST provide 'type' and 'id', found the following keys: {$providedKeys}");
        }
        if (array_key_exists(ContentField::META, $content)) {
            throw new InvalidArgumentException('meta can not be validated yet hence it is not accepted at all');
        }
        $this->id = $content[ContentField::ID];
        if (!is_string($this->id)) {
            throw new InvalidArgumentException('id is not given as string');
        }
        $this->type = $content[ContentField::TYPE];
        if (!is_string($this->type)) {
            throw new InvalidArgumentException('type is not given as string');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
