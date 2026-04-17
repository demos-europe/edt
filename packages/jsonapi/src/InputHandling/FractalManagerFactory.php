<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\JsonApi\OutputHandling\PropertyReadableTypeProviderInterface;
use EDT\JsonApi\Validation\FieldsValidator;
use EDT\JsonApi\Validation\IncludeValidator;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\TypeRetrievalAccessException;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use League\Fractal\Manager;
use League\Fractal\Serializer\JsonApiSerializer;

class FractalManagerFactory
{
    /**
     * @param int<0,max> $recursionLimit Upper limit to how many levels of included data are allowed.
     */
    public function __construct(
        protected readonly PropertyReadableTypeProviderInterface $typeProvider,
        protected readonly IncludeValidator $includeValidator,
        protected readonly FieldsValidator $fieldsValidator,
        protected readonly int $recursionLimit,
    ) {
    }

    /**
     * @param PropertyReadableTypeInterface<PathsBasedInterface, PathsBasedInterface, object> $type
     * @param string|null $rawIncludes
     * @param string|null $rawExcludes The JSON:API specification does not support excludes, but Fractal does.
     *
     * @throws TypeRetrievalAccessException
     */
    public function createFractalManager(
        PropertyReadableTypeInterface $type,
        ?string $rawIncludes,
        ?string $rawExcludes,
        ?string $rawFieldsets
    ): Manager {
        $fractalManager = new Manager();
        $fractalManager->setSerializer(new JsonApiSerializer());
        $fractalManager->setRecursionLimit($this->recursionLimit);

        // process includes
        if (null !== $rawIncludes) {
            $includes = $this->includeValidator->assertIncludeFormat($rawIncludes);
            $this->includeValidator->assertIncludesAgainstType($includes, $type);
            $fractalManager->parseIncludes($rawIncludes);
        }

        // process excludes
        if (null !== $rawExcludes) {
            $fractalManager->parseExcludes($rawExcludes);
        }

        // process fieldsets
        if (null !== $rawFieldsets) {
            $fieldsets = $this->fieldsValidator->validateFormat($rawFieldsets);
            foreach ($fieldsets as $typeName => $fieldsString) {
                $fieldsetType = $this->typeProvider->getType($typeName);
                $this->fieldsValidator->getNonReadableProperties($fieldsString, $fieldsetType);
            }
            $fractalManager->parseFieldsets($fieldsets);
        }

        return $fractalManager;
    }
}
