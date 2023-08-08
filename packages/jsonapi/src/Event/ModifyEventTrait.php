<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

trait ModifyEventTrait
{
    protected bool $sideEffects = false;

    public function hasSideEffects(): bool
    {
        return $this->sideEffects;
    }

    public function setSideEffects(bool $sideEffects): void
    {
        $this->sideEffects = $sideEffects;
    }
}
