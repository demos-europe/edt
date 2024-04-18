<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PropertyPathInterface;

trait AliasTrait
{
    /**
     * @var non-empty-list<non-empty-string>|null
     */
    protected ?array $aliasedPath = null;

    public function aliasedPath(array|PropertyPathInterface $aliasedPath): self
    {
        return $this->setAliasedPath($aliasedPath);
    }

    public function setAliasedPath(array|PropertyPathInterface $aliasedPath): self
    {
        $this->aliasedPath = $aliasedPath instanceof PropertyPathInterface
            ? $aliasedPath->getAsNames()
            : $aliasedPath;

        return $this;
    }

    public function removeAliasedPath(): self
    {
        $this->aliasedPath = null;

        return $this;
    }
}
