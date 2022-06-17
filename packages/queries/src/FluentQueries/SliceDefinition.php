<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use InvalidArgumentException;

class SliceDefinition
{
    /**
     * @var int
     */
    private $offset = 0;
    /**
     * @var int|null
     */
    private $limit;

    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return $this
     */
    public function setOffset(int $offset): self
    {
        if (0 > $offset) {
            throw new InvalidArgumentException("Negative offset ($offset) is not supported.");
        }
        $this->offset = $offset;
        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return $this
     */
    public function setLimit(?int $limit): self
    {
        if (0 > $limit) {
            throw new InvalidArgumentException("Negative limit ($limit) is not supported.");
        }
        $this->limit = $limit;
        return $this;
    }
}
