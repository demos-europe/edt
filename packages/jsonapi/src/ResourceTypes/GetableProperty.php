<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

interface GetableProperty
{
    public function isReadable(): bool;

    /**
     * @return non-empty-string
     */
    public function getName(): string;

    public function isSortable(): bool;

    public function isFilterable(): bool;

    /**
     * @return non-empty-list<non-empty-string>|null
     */
    public function getAliasedPath(): ?array;

    public function isDefaultField(): bool;

    public function isDefaultInclude(): bool;

    /**
     * @return non-empty-string|null
     */
    public function getTypeName(): ?string;

    public function getCustomReadCallback(): ?callable;

    public function isAllowingInconsistencies(): bool;

    public function isRelationship(): bool;

    public function isInitializable(): bool;

    public function isRequiredForCreation(): bool;
}
