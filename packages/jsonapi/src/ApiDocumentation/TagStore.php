<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Tag;
use function array_key_exists;

/**
 * @internal
 */
class TagStore
{
    /**
     * @var array<non-empty-string, Tag>
     */
    protected array $tags = [];

    /**
     * @return list<Tag>
     */
    public function getTags(): array
    {
        return array_values($this->tags);
    }

    /**
     * @param non-empty-string $typeName
     * @param non-empty-string $tagName
     */
    public function getOrCreateTag(string $typeName, string $tagName): Tag
    {
        if (!array_key_exists($typeName, $this->tags)) {
            $tag = $this->createTag($tagName);
            $this->tags[$typeName] = $tag;
        }

        return $this->tags[$typeName];
    }

    /**
     * @param non-empty-string $name
     *
     * @throws TypeErrorException
     */
    protected function createTag(string $name): Tag
    {
        return new Tag([
            'name' => $name,
        ]);
    }

    public function reset(): void
    {
        $this->tags = [];
    }
}
