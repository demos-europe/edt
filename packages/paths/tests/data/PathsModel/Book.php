<?php

declare(strict_types=1);

namespace Tests\data\PathsModel;

use Tests\data\Model\Person;

class Book
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var Person
     */
    protected $author;

    /**
     * @var string[]
     */
    protected $tags;

    public function __construct(string $title, Person $author, string ...$tags)
    {
        $this->title = $title;
        $this->author = $author;
        $this->tags = $tags;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
