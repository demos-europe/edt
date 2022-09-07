<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

interface GetableProperty
{
    public function isReadable(): bool;

    public function getName(): string;

    public function isSortable(): bool;

    public function isFilterable(): bool;

    /**
     * @return non-empty-array<int,string>|null
     */
    public function getAliasedPath(): ?array;

    public function isDefaultField(): bool;

    public function isDefaultInclude(): bool;

    public function getTypeName(): ?string;

    public function getCustomReadCallback(): ?callable;

    public function isAllowingInconsistencies(): bool;

    public function isRelationship(): bool;

    public function isInitializable(): bool;

    public function isRequiredForCreation(): bool;
}
