<?php

declare(strict_types=1);

namespace Tests\data\Model;

class Book
{
    /**
     * @param list<non-empty-string> $tags
     */
    public function __construct(
        protected string $title,
        protected Person $author,
        protected array $tags = [],
        protected int $id = 0
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }
}
