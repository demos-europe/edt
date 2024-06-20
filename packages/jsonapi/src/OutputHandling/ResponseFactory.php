<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputHandling;

use EDT\JsonApi\InputHandling\FractalManagerFactory;
use EDT\JsonApi\RequestHandling\RequestHeader;
use EDT\JsonApi\RequestHandling\UrlParameter;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use League\Fractal\Resource\ResourceAbstract;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;
use function array_key_exists;
use const JSON_OBJECT_AS_ARRAY;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class ResponseFactory
{
    private const VERSION = '1.0';

    public function __construct(
        protected readonly FractalManagerFactory $fractalManagerFactory
    ) {}

    /**
     * @param PropertyReadableTypeInterface<object> $type
     * @param int<100,599> $statusCode
     */
    public function createResourceResponse(
        Request $request,
        ResourceAbstract $resource,
        PropertyReadableTypeInterface $type,
        int $statusCode
    ): JsonResponse {
        $rawIncludes = $request->get(UrlParameter::INCLUDE);
        Assert::nullOrString($rawIncludes);
        $rawFieldsets = $request->get(UrlParameter::FIELDS);
        Assert::nullOrString($rawFieldsets);
        $rawExcludes = $request->get(UrlParameter::EXCLUDE);
        // TODO (#155): add validation of exclude definitions and verify specification compatibility with JSON:API before allowing this feature
        Assert::null($rawExcludes);

        $fractalManager = $this->fractalManagerFactory->createFractalManager(
            $type,
            $rawIncludes,
            $rawExcludes,
            $rawFieldsets
        );
        $data = $fractalManager->createData($resource)->toArray();
        Assert::isArray($data);

        if (array_key_exists(ContentField::INCLUDED, $data)) {
            // TODO (#157): investigate if correct
            $data[ContentField::INCLUDED] = [];
        }

        $data[ContentField::LINKS] = [ContentField::SELF => $request->getUri()];
        $data[ContentField::JSONAPI] = [ContentField::VERSION => self::VERSION];

        $response = new JsonResponse($data, $statusCode, [], false);
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_OBJECT_AS_ARRAY);
        $response->headers->set(RequestHeader::CONTENT_TYPE_KEY, RequestHeader::CONTENT_TYPE_VALUE);

        return $response;
    }
}
