<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

trait ModifyEventTrait
{
    /**
     * @var list<non-empty-string>
     */
    protected array $requestDeviations = [];

    /**
     * @return list<non-empty-string>
     */
    public function getRequestDeviations(): array
    {
        return $this->requestDeviations;
    }

    /**
     * @param list<non-empty-string> $requestDeviations
     */
    public function setRequestDeviations(array $requestDeviations): void
    {
        $this->requestDeviations = $requestDeviations;
    }
}
