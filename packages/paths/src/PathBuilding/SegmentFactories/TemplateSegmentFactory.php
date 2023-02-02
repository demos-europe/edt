<?php

declare(strict_types=1);

namespace EDT\PathBuilding\SegmentFactories;

use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\Querying\Contracts\PropertyPathInterface;

/**
 * Utilizes the retrieval and cloning of an instance via the given provider, and only falls back on
 * other initialization techniques if the provider was not able to deliver.
 *
 * The retrieved instance is cloned and that clone used as next path segment.
 *
 * This approach is useful if the classes denoted in the docblock via &#x00040;`property-read`
 * tags (or likewise) are not only paths but more complex classes (e.g. edt-access-definition
 * types) and if they are already available in the application (e.g. via the Symfony
 * service container). In such a case cloning them may result in more properly initialized
 * instances than simply initializing them via reflection.
 *
 * *Note that you can get the Symfony container by implementing the
 * {@link ContainerAwareInterface}. You may also need to mark the relevant Symfony services as
 * `public: true` for them to be retrievable from the Symfony container.*
 */
class TemplateSegmentFactory implements SegmentFactoryInterface
{
    public function __construct(
        private readonly SegmentFactoryInterface $fallbackFactory,
        private readonly SegmentTemplateProviderInterface $segmentTemplateProvider
    ) {}

    public function createNextSegment(
        string $returnType,
        PropertyAutoPathInterface $parent,
        string $parentPropertyName
    ): PropertyPathInterface {
        if ($this->segmentTemplateProvider->isTemplateNotAvailable($returnType)) {
            return $this->fallbackFactory->createNextSegment($returnType, $parent, $parentPropertyName);
        }

        $returnInstance = $this->segmentTemplateProvider->getTemplate($returnType);
        if (null === $returnInstance) {
            return $this->fallbackFactory->createNextSegment($returnType, $parent, $parentPropertyName);
        }

        $childPathSegment = clone $returnInstance;
        $childPathSegment->setParent($parent);
        $childPathSegment->setParentPropertyName($parentPropertyName);

        return $childPathSegment;
    }
}
