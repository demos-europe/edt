<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\ConditionFactory\DrupalFilterInterface;
use EDT\ConditionFactory\ConditionFactory;
use EDT\JsonApi\InputHandling\FractalManagerFactory;
use EDT\JsonApi\OutputHandling\PropertyReadableTypeProviderInterface;
use EDT\JsonApi\OutputHandling\ResponseFactory;
use EDT\JsonApi\Pagination\PagePaginationParser;
use EDT\JsonApi\RequestHandling\JsonApiSortingParser;
use EDT\JsonApi\RequestHandling\PaginatorFactory;
use EDT\JsonApi\RequestHandling\RequestConstraintFactory;
use EDT\JsonApi\RequestHandling\RequestWithBody;
use EDT\JsonApi\Validation\FieldsValidator;
use EDT\JsonApi\Validation\IncludeValidator;
use EDT\JsonApi\Validation\SortValidator;
use EDT\Querying\ConditionParsers\Drupal\DrupalConditionParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterValidator;
use EDT\Querying\ConditionParsers\Drupal\PredefinedDrupalConditionFactory;
use EDT\Querying\SortMethodFactories\SortMethodFactory;
use EDT\Wrapping\Utilities\PropertyPathProcessorFactory;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DefaultProcessorConfig implements GetProcessorConfigInterface, ListProcessorConfigInterface, CreationProcessorConfigInterface, UpdateProcessorConfigInterface, DeletionProcessorConfigInterface
{
    /**
     * @param int<0,max> $fractalRecursionLimit
     * @param int<0,8192> $attributeValidationDepth
     * @param int<1,8192> $maxBodyNestingDepth see {@link RequestWithBody::getRequestBody()}
     */
    public function __construct(
        protected readonly ValidatorInterface $validator,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly Router $router,
        protected readonly int $fractalRecursionLimit = 20,
        protected readonly int $attributeValidationDepth = 20,
        protected readonly bool $attributeAllowAnythingBelowValidationDepth = true,
        protected readonly int $maxBodyNestingDepth = 512
    ) {}

    /**
     * @return DrupalFilterParser<DrupalFilterInterface>
     */
    public function getFilterTransformer(): DrupalFilterParser
    {
        return new DrupalFilterParser($this->getConditionFactory(), $this->getDrupalConditionTransformer(), $this->getFilterValidator());
    }

    public function getSortingTransformer(): JsonApiSortingParser
    {
        return new JsonApiSortingParser($this->getSortMethodFactory());
    }

    public function getPaginatorFactory(): PaginatorFactory
    {
        return new PaginatorFactory($this->router);
    }

    public function getPagPaginatorTransformer(int $defaultPaginationPageSize): PagePaginationParser
    {
        return new PagePaginationParser($defaultPaginationPageSize, $this->validator);
    }

    public function getSchemaPathProcessor(): SchemaPathProcessor
    {
        return new SchemaPathProcessor($this->getPropertyPathProcessorFactory());
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getSortingValidator(): SortValidator
    {
        return new SortValidator($this->validator);
    }

    public function getFilterValidator(): DrupalFilterValidator
    {
        return new DrupalFilterValidator($this->validator, $this->getDrupalConditionFactory());
    }

    public function getResponseFactory(PropertyReadableTypeProviderInterface $typeProvider): ResponseFactory
    {
        return new ResponseFactory($this->getFractalManagerFactory($typeProvider));
    }

    public function getRequestConstraintFactory(): RequestConstraintFactory
    {
        return new RequestConstraintFactory(
            $this->attributeValidationDepth,
            $this->attributeAllowAnythingBelowValidationDepth
        );
    }

    /**
     * @return PredefinedDrupalConditionFactory<DrupalFilterInterface>
     */
    protected function getDrupalConditionFactory(): PredefinedDrupalConditionFactory
    {
        return new PredefinedDrupalConditionFactory($this->getConditionFactory());
    }

    protected function getFractalManagerFactory(PropertyReadableTypeProviderInterface $typeProvider): FractalManagerFactory
    {
        return new FractalManagerFactory(
            $typeProvider,
            $this->getIncludeValidator(),
            $this->getFieldsValidator(),
            $this->fractalRecursionLimit,
        );
    }

    protected function getIncludeValidator(): IncludeValidator
    {
        return new IncludeValidator($this->getSchemaPathProcessor());
    }

    protected function getPropertyPathProcessorFactory(): PropertyPathProcessorFactory
    {
        return new PropertyPathProcessorFactory();
    }

    /**
     * @return DrupalConditionParser<DrupalFilterInterface>
     */
    protected function getDrupalConditionTransformer(): DrupalConditionParser
    {
        return new DrupalConditionParser($this->getDrupalConditionFactory());
    }

    protected function getFieldsValidator(): FieldsValidator
    {
        return new FieldsValidator($this->validator);
    }

    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    public function getMaxBodyNestingDepth(): int
    {
        return $this->maxBodyNestingDepth;
    }

    protected function getConditionFactory(): ConditionFactory
    {
        return new ConditionFactory();
    }

    protected function getSortMethodFactory(): SortMethodFactory
    {
        return new SortMethodFactory();
    }
}
