<?php

declare(strict_types=1);

namespace EDT\PathBuilding\SegmentFactories;

use EDT\PathBuilding\PropertyAutoPathInterface;

/**
 * {@link self::getTemplate()} may return `null`, even if {@link self::isTemplateNotAvailable()}
 * returned `false`.
 *
 * The reasoning is that the call to {@link self::getTemplate()} may be costly. So if there is a
 * way to detect beforehand that a template is not available anyway, it can be implemented via
 * {@link self::isTemplateNotAvailable()}, thus skipping the {@link self::getTemplate()} call.
 */
interface SegmentTemplateProviderInterface
{
    /**
     * @param class-string<PropertyAutoPathInterface> $className
     */
    public function getTemplate(string $className): ?PropertyAutoPathInterface;

    /**
     * @param class-string<PropertyAutoPathInterface> $className
     */
    public function isTemplateNotAvailable(string $className): bool;
}
