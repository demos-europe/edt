<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Components;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Tag;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function count;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class OpenAPISchemaGenerator
{
    /**
     * @param list<TransferableTypeInterface<TCondition, TSorting, object>> $primaryExposedResourceTypes
     * @param int<0, max> $defaultPageSize
     */
    public function __construct(
        protected readonly array $primaryExposedResourceTypes,
        protected readonly RouterInterface $router,
        protected readonly SchemaStore $schemaStore,
        protected readonly TranslatorInterface $translator,
        protected readonly int $defaultPageSize
    ) {}

    /**
     * @throws TypeErrorException
     */
    public function getOpenAPISpecification(): OpenApi
    {
        $openApi = new OpenApi(
            [
                'openapi' => '3.0.2',
                'info'    => [
                    'title'       => $this->trans('title'),
                    'description' => $this->trans('description'),
                    'version'     => '2.0',
                ],
                'paths'   => [],
                'tags'    => [],
            ]
        );

        // remove non-directly accessible ones
        $openApi->tags = array_values(array_map(function (TransferableTypeInterface $type) use ($openApi): Tag {
            // add routing information for directly accessible resource types
            $typeName = $type->getTypeName();
            $tag = $this->createTag($typeName);

            $listMethodPathItem = new PathItem([]);
            $this->addListOperation($tag, $type, $listMethodPathItem);

            $entityMethodsPathItem = $this->createEntityMethodsPathItem();
            $this->addGetOperation($tag, $type, $entityMethodsPathItem);

            $baseUrl = $this->router->generate(
                'api_resource_list',
                ['resourceType' => $typeName]
            );

            $openApi->paths[$baseUrl] = $listMethodPathItem;
            $openApi->paths["$baseUrl/{resourceId}/"] = $entityMethodsPathItem;

            return $tag;
        }, $this->primaryExposedResourceTypes));

        $openApi->components = new Components(['schemas' => $this->schemaStore->getSchemas()]);

        return $openApi;
    }

    /**
     * @param non-empty-string $typeName
     *
     * @throws TypeErrorException
     */
    protected function createTag(string $typeName): Tag
    {
        return new Tag([
            'name' => $this->trans(
                'resource.section',
                ['type' => $typeName]
            ),
        ]);
    }

    /**
     * @param TransferableTypeInterface<PathsBasedInterface, PathsBasedInterface, object> $type
     *
     * @throws TypeErrorException
     */
    protected function addListOperation(
        Tag $tag,
        TransferableTypeInterface $type,
        PathItem $pathItem
    ): void {
        $okResponse = new Response([
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => $this->wrapAsJsonApiResponseSchema(
                        $type->getTypeName(),
                        [
                            'type'  => 'array',
                            'items' => [
                                '$ref' => $this->schemaStore->createTypeSchemaAndGetReference($type),
                            ],
                        ],
                        [],
                        true
                    ),
                ],
            ],
        ]);

        $pathItem->get = new Operation([
            'description' => $this->trans(
                'method.list.description',
                ['type' => $type->getTypeName()]
            ),
            'parameters' => array_merge(
                $this->getDefaultQueryParameters(),
                $this->getPaginationParameters(),
                $this->getFilterParameter()
            ),
            'responses' => [
                SymfonyResponse::HTTP_OK => $okResponse,
            ],
            'tags' => [$tag->name],
        ]);
    }

    /**
     * @param TransferableTypeInterface<PathsBasedInterface, PathsBasedInterface, object> $type
     *
     * @throws TypeErrorException
     */
    protected function addGetOperation(
        Tag $tag,
        TransferableTypeInterface $type,
        PathItem $pathItem
    ): void {
        $okResponse = new Response([
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => $this->wrapAsJsonApiResponseSchema(
                        $type->getTypeName(),
                        [
                            '$ref' => $this->schemaStore->createTypeSchemaAndGetReference($type),
                        ],
                        [],
                        false
                    ),
                ],
            ],
        ]);

        $pathItem->get = new Operation([
            'description' => $this->trans(
                'method.get.description',
                ['type' => $type->getTypeName()]
            ),
            'responses'   => [
                SymfonyResponse::HTTP_OK => $okResponse,
            ],
            'tags'        => [$tag->name],
        ]);
    }

    /**
     * @throws TypeErrorException
     */
    protected function createEntityMethodsPathItem(): PathItem
    {
        return new PathItem([
            'parameters' => array_merge(
                $this->getDefaultQueryParameters(),
                [
                    new Parameter(
                        [
                            'in'          => 'path',
                            'name'        => 'resourceId',
                            'description' => $this->trans('resource.id'),
                        ]
                    ),
                ]
            ),
        ]);
    }

    /**
     * @param non-empty-string $typeName
     * @param array{type: non-empty-string, items: array<non-empty-string, non-empty-string>}|array<non-empty-string, non-empty-string> $dataObjects
     * @param array<non-empty-string, mixed> $includedObjects
     *
     * @throws TypeErrorException
     */
    protected function wrapAsJsonApiResponseSchema(
        string $typeName,
        array $dataObjects,
        array $includedObjects,
        bool $isList
    ): Schema {
        $data = [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'default' => $typeName],
                'attributes' => $dataObjects,
            ],
        ];

        if ($isList) {
            $data = [
                'type' => 'array',
                'items' => $data,
            ];
        }

        $selfLink = $this->router->generate('api_resource_list', ['resourceType' => $typeName]);

        if (!$isList) {
            $selfLink .= '{resourceId}/';
        }

        $jsonApiResponse = [
            'type' => 'object',
            'properties' => [
                'jsonapi' => [
                    'type' => 'object',
                    'properties' => [
                        'version' => ['type' => 'string', 'default' => '1.0'],
                    ],
                ],
                'data' => $data,
                'meta' => ['type' => 'object'],
                'links' => [
                    'type' => 'object',
                    'properties' => [
                        'self' => [
                            'type' => 'string',
                            'default' => $selfLink,
                        ],
                    ],
                ],
            ],
        ];

        if (0 < count($includedObjects)) {
            $jsonApiResponse['properties']['included'] = [
                'type' => 'array',
                'items' => $includedObjects,
            ];
        }

        return new Schema($jsonApiResponse);
    }

    /**
     * @return list<Parameter>
     *
     * @throws TypeErrorException
     */
    protected function getDefaultQueryParameters(): array
    {
        $this->schemaStore->findOrCreate(
            'parameters:include',
            static fn (): Schema => new Schema(['type' => 'array'])
        );

        $this->schemaStore->findOrCreate(
            'parameters:exclude',
            static fn (): Schema => new Schema(['type' => 'array'])
        );

        return [
            new Parameter(
                [
                    'in'          => 'query',
                    'name'        => 'include',
                    'description' => $this->trans('parameter.query.include'),
                    'schema'      => [
                        '$ref' => $this->schemaStore->getReference('parameters:include'),
                    ],
                ]
            ),
            new Parameter(
                [
                    'in'          => 'query',
                    'name'        => 'exclude',
                    'description' => $this->trans('parameter.query.exclude'),
                    'schema'      => [
                        '$ref' => $this->schemaStore->getReference('parameters:exclude'),
                    ],
                ]
            ),
        ];
    }

    /**
     * @return list<Parameter>
     *
     * @throws TypeErrorException
     */
    protected function getPaginationParameters(): array
    {
        $this->schemaStore->findOrCreate(
            'parameter:pagination_number',
            static fn (): Schema => new Schema([
                'type'    => 'number',
                'format'  => 'int64',
                'default' => 1,
            ])
        );

        $this->schemaStore->findOrCreate(
            'parameter:pagination_size',
            fn (): Schema => new Schema([
                'type'    => 'number',
                'format'  => 'int64',
                'default' => $this->defaultPageSize,
            ])
        );

        return [
            new Parameter(
                [
                    'in'          => 'query',
                    'name'        => 'page[number]',
                    'description' => $this->trans('parameter.query.page_number'),
                    'schema' => [
                        '$ref' => $this->schemaStore->getReference('parameter:pagination_number'),
                    ],
                ]
            ),
            new Parameter([
                'in'          => 'query',
                'name'        => 'page[size]',
                'description' => $this->trans('parameter.query.page_size'),
                'schema' => [
                    '$ref' => $this->schemaStore->getReference('parameter:pagination_size'),
                ],
            ]),
        ];
    }

    /**
     * @return list<Parameter>
     *
     * @throws TypeErrorException
     */
    protected function getFilterParameter(): array
    {
        $this->schemaStore->findOrCreate(
            'parameter:filter',
            static fn (): Schema => new Schema([
                'type' => 'array',
            ])
        );

        return [
            new Parameter(
                [
                    'in'          => 'query',
                    'name'        => 'filter',
                    'description' => $this->trans('parameter.query.filter'),
                    'schema' => [
                        '$ref' => $this->schemaStore->getReference('parameter:filter'),
                    ],
                ]
            ),
        ];
    }

    /**
     * @param string               $id         #TranslationKey
     * @param array<string, mixed> $parameters
     */
    protected function trans(string $id, array $parameters = []): string
    {
        return trim($this->translator->trans($id, $parameters, 'openapi', 'en'));
    }
}
