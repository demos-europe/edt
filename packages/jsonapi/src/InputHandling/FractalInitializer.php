<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\JsonApi\RequestHandling\UrlParameter;
use EDT\JsonApi\Validation\FieldsValidator;
use EDT\JsonApi\Validation\IncludeValidator;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use League\Fractal\Manager;
use League\Fractal\Serializer\JsonApiSerializer;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

class FractalInitializer
{
    /**
     * The JSON:API specification does not support excludes, but Fractal does.
     */
    protected readonly bool $allowExcludes;

    /**
     * @param TypeProviderInterface<PathsBasedInterface, PathsBasedInterface> $typeProvider
     * @param non-empty-string $urlResourceType The placeholder in your URL path representing the resource type name
     * @param int<0,max> $recursionLimit Upper limit to how many levels of included data are allowed.
     */
    public function __construct(
        protected readonly TypeProviderInterface $typeProvider,
        protected readonly IncludeValidator $includeValidator,
        protected readonly FieldsValidator $fieldsValidator,
        protected readonly string $urlResourceType,
        protected readonly int $recursionLimit,
    ) {
        // TODO: validation of exclude definitions needs to be added before allowing this feature
        $this->allowExcludes = false;
    }

    public function initializeFractalManager(Request $request): Manager
    {
        $fractalManager = new Manager();
        $fractalManager->setSerializer(new JsonApiSerializer());
        $fractalManager->setRecursionLimit($this->recursionLimit);

        // process type
        $typeIdentifier = $request->attributes->get($this->urlResourceType);
        Assert::stringNotEmpty($typeIdentifier);
        $type = $this->typeProvider->getTypeByIdentifier($typeIdentifier);

        // process includes
        $rawIncludes = $request->get(UrlParameter::INCLUDE);
        if (null !== $rawIncludes) {
            Assert::string($rawIncludes);
            $includes = $this->includeValidator->assertIncludeFormat($rawIncludes);
            Assert::isInstanceOf($type, PropertyReadableTypeInterface::class);
            $this->includeValidator->assertIncludesAgainstType($includes, $type);
            $fractalManager->parseIncludes($rawIncludes);
        }

        // process excludes
        if ($this->allowExcludes) {
            $rawExcludes = $request->get('exclude');
            if (null !== $rawExcludes) {
                Assert::string($rawExcludes);
                $fractalManager->parseExcludes($rawExcludes);
            }
        }

        // process fieldsets
        $rawFieldsets = $request->get(UrlParameter::FIELDS);
        if (null !== $rawFieldsets) {
            $fieldsets = $this->fieldsValidator->validateFormat($rawFieldsets);
            foreach ($fieldsets as $fieldsetTypeIdentifier => $fieldsString) {
                $fieldsetType = $this->typeProvider->getTypeByIdentifier($fieldsetTypeIdentifier);
                Assert::isInstanceOf($fieldsetType, PropertyReadableTypeInterface::class);
                $this->fieldsValidator->getNonReadableProperties($fieldsString, $fieldsetType);
            }
            $fractalManager->parseFieldsets($fieldsets);
        }

        return $fractalManager;
    }
}
