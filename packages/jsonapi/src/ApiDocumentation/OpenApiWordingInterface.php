<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

interface OpenApiWordingInterface
{
    public function getOpenApiDescription(): string;

    /**
     * @return non-empty-string
     */
    public function getOpenApiTitle(): string;

    public function getIncludeParameterDescription(): string;

    public function getPageNumberParameterDescription(): string;

    public function getPageSizeParameterDescription(): string;

    public function getFilterParameterDescription(): string;

    /**
     * @param non-empty-string $typeName
     *
     * @return non-empty-string
     */
    public function getTagName(string $typeName): string;
}
