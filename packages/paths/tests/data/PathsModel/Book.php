<?php

declare(strict_types=1);

namespace Tests\data\PathsModel;

use Tests\data\Model\Person;

class Book
{
    /**
     * @param list<non-empty-string> $tags
     */
    public function __construct(
        protected string $title,
        protected Person $author,
        protected string $tags
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }
}
