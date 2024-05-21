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
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use function count;

/**
 * Generate a schema for {@link https://swagger.io/specification/ OpenApi}.
 *
 * To activate the generation of documentation for a specific kind of action, make sure to call the corresponding
 * setter method, e.g. {@link self::setGetActionConfig} and {@link self::setListActionConfig}.
 * You can change these configurations at any time to {@link self::buildDocument() generate} the next document based
 * on a different configuration.
 *
 * **This implementation is to be considered WIP.**
 * It has not yet been evaluated if the generated schema is valid.
 * Most application dependent information can be configured, but the implementation still makes assumptions about your
 * application (e.g. Symfony route attributes), that may not be accurate.
 */
class OpenApiDocumentBuilder
{
    protected ?ActionConfigInterface $getActionConfig = null;
    protected ?ActionConfigInterface $listActionConfig = null;

    /**
     * @param int<0, max> $defaultPageSize
     * @param array<non-empty-string, PropertyReadableTypeInterface<object>> $getableTypes
     * @param array<non-empty-string, PropertyReadableTypeInterface<object>> $listableTypes
     */
    public function __construct(
        protected readonly SchemaStore $schemaStore,
        protected readonly TagStore $tagStore,
        protected readonly int $defaultPageSize,
        protected readonly array $getableTypes,
        protected readonly array $listableTypes,
    ) {}

    /**
     * @throws TypeErrorException
     */
    public function buildDocument(OpenApiWordingInterface $wording): OpenApi
    {
        $this->schemaStore->reset();
        $this->tagStore->reset();

        $openApi = new OpenApi([
            'openapi' => '3.0.2',
            'info'    => $this->setDescription($wording->getOpenApiDescription(), [
                'title' => $wording->getOpenApiTitle(),
                'version' => '2.0',
            ]),
            'paths'   => [],
            'tags'    => [],
        ]);


        $getActionConfig = $this->getActionConfig;
        if (null !== $getActionConfig) {
            foreach ($this->getableTypes as $typeName => $type) {
                $tag = $this->tagStore->getOrCreateTag($typeName, $wording->getTagName($typeName));
                $entityMethodsPathItem = $this->createEntityMethodsPathItem($getActionConfig, $wording);
                $this->addGetOperation($tag, $type, $typeName, $entityMethodsPathItem, $getActionConfig);
                $baseUrl = $getActionConfig->getSelfLink($typeName);
                $this->addPath($openApi, $baseUrl, $entityMethodsPathItem);
            }
        }

        $listActionConfig = $this->listActionConfig;
        if (null !== $listActionConfig) {
            foreach ($this->listableTypes as $typeName => $type) {
                $tag = $this->tagStore->getOrCreateTag($typeName, $wording->getTagName($typeName));
                $pathItem = new PathItem([]);
                $this->addListOperation($tag, $type, $typeName, $pathItem, $listActionConfig, $wording);
                $baseUrl = $listActionConfig->getSelfLink($typeName);
                $this->addPath($openApi, $baseUrl, $pathItem);
            }
        }

        $openApi->tags = $this->tagStore->getTags();
        $openApi->components = new Components(['schemas' => $this->schemaStore->getSchemas()]);

        return $openApi;
    }

    protected function addPath(OpenApi $openApi, string $baseUrl, PathItem $pathItem): void
    {
        $this->schemaStore->createPendingSchemas();
        $openApi->paths[$baseUrl] = $pathItem;
    }

    /**
     * @param PropertyReadableTypeInterface<object> $type
     * @param non-empty-string $typeName
     *
     * @throws TypeErrorException
     */
    protected function addListOperation(
        Tag $tag,
        PropertyReadableTypeInterface $type,
        string $typeName,
        PathItem $pathItem,
        ActionConfigInterface $listActionConfig,
        OpenApiWordingInterface $translator
    ): void {
        $okResponse = new Response([
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => $this->wrapAsJsonApiResponseSchema(
                        $typeName,
                        [
                            'type'  => 'array',
                            'items' => [
                                '$ref' => $this->schemaStore->noteTypeForSchemaCreationAndGetReference($type, $typeName),
                            ],
                        ],
                        [],
                        $listActionConfig,
                        true
                    ),
                ],
            ],
        ]);

        $this->schemaStore->createPendingSchemas();

        $pathItem->get = new Operation(
            $this->setDescription($listActionConfig->getOperationDescription($typeName), [
                'parameters' => array_merge(
                    $this->getDefaultQueryParameters($translator),
                    $this->getPaginationParameters($translator),
                    $this->getFilterParameter($translator)
                ),
                'responses' => [
                    SymfonyResponse::HTTP_OK => $okResponse,
                ],
                'tags' => [$tag->name],
            ]));
    }

    /**
     * @param PropertyReadableTypeInterface<object> $type
     * @param non-empty-string $typeName
     *
     * @throws TypeErrorException
     */
    protected function addGetOperation(
        Tag $tag,
        PropertyReadableTypeInterface $type,
        string $typeName,
        PathItem $pathItem,
        ActionConfigInterface $getActionConfig
    ): void {
        $okResponse = new Response([
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => $this->wrapAsJsonApiResponseSchema(
                        $typeName,
                        [
                            '$ref' => $this->schemaStore->noteTypeForSchemaCreationAndGetReference($type, $typeName),
                        ],
                        [],
                        $getActionConfig,
                        false
                    ),
                ],
            ],
        ]);

        $this->schemaStore->createPendingSchemas();
        $pathItem->get = new Operation($this->setDescription($getActionConfig->getOperationDescription($typeName), [
            'responses' => [
                SymfonyResponse::HTTP_OK => $okResponse,
            ],
            'tags' => [$tag->name],
        ]));
    }

    /**
     * @throws TypeErrorException
     */
    protected function createEntityMethodsPathItem(ActionConfigInterface $getActionConfig, OpenApiWordingInterface $translator): PathItem
    {
        return new PathItem([
            'parameters' => array_merge(
                $this->getDefaultQueryParameters($translator),
                [
                    new Parameter($this->setDescription($getActionConfig->getPathDescription(), [
                        'in' => 'path',
                        'name' => 'resourceId',
                    ])),
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
        ActionConfigInterface $config,
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
                            'default' => $config->getSelfLink($typeName),
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
    protected function getDefaultQueryParameters(OpenApiWordingInterface $wording): array
    {
        $result = [];
/*
        if ($this->enableExclude) {
            $this->schemaStore->findOrCreate(
                'parameters:exclude',
                static fn (): Schema => new Schema(['type' => 'array'])
            );

            $result[] = new Parameter($this->setDescription($translator->getExcludeParameterDescription(), [
                'in' => 'query',
                'name' => 'exclude',
                'schema' => [
                    '$ref' => $this->schemaStore->getReference('parameters:exclude'),
                ],
            ]));
        }*/

        $this->schemaStore->findOrCreate(
            'parameters:include',
            static fn (): Schema => new Schema(['type' => 'array'])
        );

        $result[] = new Parameter($this->setDescription($wording->getIncludeParameterDescription(), [
            'in' => 'query',
            'name' => 'include',
            'schema' => [
                '$ref' => $this->schemaStore->getReference('parameters:include'),
            ],
        ]));

        return $result;
    }

    /**
     * @return list<Parameter>
     *
     * @throws TypeErrorException
     */
    protected function getPaginationParameters(OpenApiWordingInterface $wording): array
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
            new Parameter($this->setDescription($wording->getPageNumberParameterDescription(), [
                'in' => 'query',
                'name' => 'page[number]',
                'schema' => [
                    '$ref' => $this->schemaStore->getReference('parameter:pagination_number'),
                ],
            ])),
            new Parameter($this->setDescription($wording->getPageSizeParameterDescription(), [
                'in' => 'query',
                'name' => 'page[size]',
                'schema' => [
                    '$ref' => $this->schemaStore->getReference('parameter:pagination_size'),
                ],
            ])),
        ];
    }

    /**
     * @return list<Parameter>
     *
     * @throws TypeErrorException
     */
    protected function getFilterParameter(OpenApiWordingInterface $wording): array
    {
        $this->schemaStore->findOrCreate(
            'parameter:filter',
            static fn (): Schema => new Schema([
                'type' => 'array',
            ])
        );

        return [new Parameter($this->setDescription($wording->getFilterParameterDescription(), [
            'in' => 'query',
            'name' => 'filter',
            'schema' => [
                '$ref' => $this->schemaStore->getReference('parameter:filter'),
            ],
        ]))];
    }

    /**
     * @param array<non-empty-string, mixed> $data
     * @return array<non-empty-string, mixed>
     */
    protected function setDescription(string $description, array $data): array
    {
        if ('' !== $description) {
            $data['description'] = $description;
        }

        return $data;
    }

    /**
     * Set the configuration to be used to generate documentation regarding JSON:API `get` requests.
     */
    public function setGetActionConfig(?ActionConfigInterface $getActionConfig): void
    {
        $this->getActionConfig = $getActionConfig;
    }

    /**
     * Set the configuration to be used to generate documentation regarding JSON:API `list` requests.
     */
    public function setListActionConfig(?ActionConfigInterface $listActionConfig): void
    {
        $this->listActionConfig = $listActionConfig;
    }
}
