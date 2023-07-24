<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Attributes;

trait OptionalInitializabilityTrait
{
    protected bool $optional = false;

    public function setOptional(bool $optional): void
    {
        $this->optional = $optional;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }
}
