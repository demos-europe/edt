<?php

declare(strict_types=1);

namespace Tests\data;

class EmptyEntity
{
    protected $id = 'abc';
    protected $a = 1;
    protected $b = 2;
    protected $c = 3;
    protected $foo;

    public function __construct()
    {
        $this->foo = new \stdClass();
        $this->foo->id = '987';
    }
}
