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
use SplObjectStorage;
use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\ConditionFactory\ConditionGroupFactoryInterface;
use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\SortMethodFactories\SortMethodInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * This class attempts to provide a config implementation with sensible defaults, that can be used by processors corresponding to different kind of requests.
 *
 * However, the implementation is by no means performance nor resource optimized and beside for quick prototyping usage of this class is discouraged.
 * Instead, you should provide your own implementation, tailored for your specific use case.
 *
 * If you use a dependency injection framework like Symfony, you may want to
 * configure the needed class instances manually as services and create
 * simple pass-through implementations for the interfaces implemented by this class.
 *
 * Beside taking advantage of singletons, this also gives you fine-grained control over the implementations to use.
 */
class DefaultProcessorConfig implements GetProcessorConfigInterface, ListProcessorConfigInterface, CreationProcessorConfigInterface, UpdateProcessorConfigInterface, DeletionProcessorConfigInterface
{
    /**
     * @var DrupalFilterParser<DrupalFilterInterface>|null
     */
    protected ?DrupalFilterParser $filterTransformer = null;

    /**
     * @var JsonApiSortingParser<SortMethodInterface>|null
     */
    protected ?JsonApiSortingParser $sortingTransformer = null;

    protected ?PaginatorFactory $paginatorFactory = null;

    protected ?SchemaPathProcessor $schemaPathProcessor = null;

    protected ?SortValidator $sortingValidator = null;

    protected ?DrupalFilterValidator $filterValidator = null;

    protected ?RequestConstraintFactory $requestConstraintFactory = null;

    /**
     * Cache instance for re-usage.
     *
     * @var (ConditionFactoryInterface<DrupalFilterInterface>&ConditionGroupFactoryInterface<DrupalFilterInterface>)|null
     */
    protected ?object $conditionFactory = null;
    
    /**
     * Cache instance for re-usage.
     *
     * @var SortMethodFactoryInterface<SortMethodInterface>|null
     */
    protected ?SortMethodFactoryInterface $sortMethodFactory = null;
    
    /**
     * @var PredefinedDrupalConditionFactory<DrupalFilterInterface>|null
     */
    protected ?PredefinedDrupalConditionFactory $drupalConditionFactory = null;

    protected ?IncludeValidator $includeValidator = null;

    protected ?PropertyPathProcessorFactory $propertyPathProcessorFactory = null;
    
    /**
     * @var DrupalConditionParser<DrupalFilterInterface>|null
     */
    protected ?DrupalConditionParser $drupalConditionTransformer = null;

    protected ?FieldsValidator $fieldsValidator = null;
    
    /**
     * @var array<positive-int, PagePaginationParser>
     */
    protected array $pagePaginationTransformers = [];
    
    /**
     * @var SplObjectStorage<PropertyReadableTypeProviderInterface, ResponseFactory>
     */
    protected readonly SplObjectStorage $responseFactories;

    /**
     * @var SplObjectStorage<PropertyReadableTypeProviderInterface, FractalManagerFactory>
     */
    protected readonly SplObjectStorage $fractalManagerFactories;
    
    
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
    ) {
        $this->responseFactories = new SplObjectStorage();
        $this->fractalManagerFactories = new SplObjectStorage();
    }

    /**
     * @return DrupalFilterParser<DrupalFilterInterface>
     */
    public function getFilterTransformer(): DrupalFilterParser
    {
        if (null === $this->filterTransformer) {
            $this->filterTransformer = new DrupalFilterParser($this->getConditionFactory(), $this->getDrupalConditionTransformer(), $this->getFilterValidator());
        }
        
        return $this->filterTransformer;
    }

    public function getSortingTransformer(): JsonApiSortingParser
    {
        if (null === $this->sortingTransformer) {
            $this->sortingTransformer = new JsonApiSortingParser($this->getSortMethodFactory());
        }
        
        return $this->sortingTransformer;
    }

    public function getPaginatorFactory(): PaginatorFactory
    {
        if (null === $this->paginatorFactory) {
            $this->paginatorFactory = new PaginatorFactory($this->getRouter());
        }
        
        return $this->paginatorFactory;
    }

    public function getPagPaginatorTransformer(int $defaultPaginationPageSize): PagePaginationParser
    {
        if (!array_key_exists($defaultPaginationPageSize, $this->pagePaginationTransformers)) {
            $this->pagePaginationTransformers[$defaultPaginationPageSize] = new PagePaginationParser($defaultPaginationPageSize, $this->getValidator());
        }

        return $this->pagePaginationTransformers[$defaultPaginationPageSize];
    }

    public function getSchemaPathProcessor(): SchemaPathProcessor
    {
        if (null === $this->schemaPathProcessor) {
            $this->schemaPathProcessor = new SchemaPathProcessor($this->getPropertyPathProcessorFactory());
        }
        
        return $this->schemaPathProcessor; 
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getSortingValidator(): SortValidator
    {
        if (null === $this->sortingValidator) {
            $this->sortingValidator = new SortValidator($this->getValidator());
        }
        
        return $this->sortingValidator;
    }

    public function getFilterValidator(): DrupalFilterValidator
    {
        if (null === $this->filterValidator) {
            $this->filterValidator = new DrupalFilterValidator($this->validator, $this->getDrupalConditionFactory());
        }
        
        return $this->filterValidator;
    }

    public function getResponseFactory(PropertyReadableTypeProviderInterface $typeProvider): ResponseFactory
    {
        if ($this->responseFactories->contains($typeProvider)) {
            $responseFactory = new ResponseFactory($this->getFractalManagerFactory($typeProvider));
            $this->responseFactories->attach($typeProvider, $responseFactory);
            
            return $responseFactory;
        }

        return $this->responseFactories->offsetGet($typeProvider);
    }

    public function getRequestConstraintFactory(): RequestConstraintFactory
    {
        if (null === $this->requestConstraintFactory) {
            $this->requestConstraintFactory = new RequestConstraintFactory(
                $this->attributeValidationDepth,
                $this->attributeAllowAnythingBelowValidationDepth
            );
        }
        
        return $this->requestConstraintFactory;
    }

    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    public function getMaxBodyNestingDepth(): int
    {
        return $this->maxBodyNestingDepth;
    }

    /**
     * @return ConditionFactoryInterface<DrupalFilterInterface>&ConditionGroupFactoryInterface<DrupalFilterInterface>
     */
    public function getConditionFactory(): ConditionFactoryInterface&ConditionGroupFactoryInterface
    {
        if (null === $this->conditionFactory) {
            $this->conditionFactory = new ConditionFactory();
        }
        
        return $this->conditionFactory;
    }
    
    /**
     * @return SortMethodFactoryInterface<SortMethodInterface>
     */
    public function getSortMethodFactory(): SortMethodFactoryInterface
    {
        if (null === $this->sortMethodFactory) {
            $this->sortMethodFactory = new SortMethodFactory();
        }
        
        return $this->sortMethodFactory;
    }

    /**
     * @return PredefinedDrupalConditionFactory<DrupalFilterInterface>
     */
    protected function getDrupalConditionFactory(): PredefinedDrupalConditionFactory
    {
        if (null === $this->drupalConditionFactory) {
            $this->drupalConditionFactory = new PredefinedDrupalConditionFactory($this->getConditionFactory());
        }
        
        return $this->drupalConditionFactory;
    }

    protected function getFractalManagerFactory(PropertyReadableTypeProviderInterface $typeProvider): FractalManagerFactory
    {
        if ($this->fractalManagerFactories->contains($typeProvider)) {
            $fractalManagerFactory = new FractalManagerFactory(
                $typeProvider,
                $this->getIncludeValidator(),
                $this->getFieldsValidator(),
                $this->fractalRecursionLimit,
            );
            $this->fractalManagerFactories->attach($typeProvider, $fractalManagerFactory);
        }

        return $this->fractalManagerFactories->offsetGet($typeProvider);
    }

    protected function getIncludeValidator(): IncludeValidator
    {
        if (null === $this->includeValidator) {
            $this->includeValidator = new IncludeValidator($this->getSchemaPathProcessor());
        }
        
        return $this->includeValidator;
    }

    protected function getPropertyPathProcessorFactory(): PropertyPathProcessorFactory
    {
        if (null === $this->propertyPathProcessorFactory) {
            $this->propertyPathProcessorFactory = new PropertyPathProcessorFactory();
        }
        
        return $this->propertyPathProcessorFactory;
    }
    
    /**
     * @return DrupalConditionParser<DrupalFilterInterface>
     */
    protected function getDrupalConditionTransformer(): DrupalConditionParser
    {
        if (null === $this->drupalConditionTransformer) {
            $this->drupalConditionTransformer = new DrupalConditionParser($this->getDrupalConditionFactory());
        }
            
        return $this->drupalConditionTransformer;
    }
    
    protected function getFieldsValidator(): FieldsValidator
    {
        if (null === $this->fieldsValidator) {
            $this->fieldsValidator = new FieldsValidator($this->getValidator());
        }
        
        return $this->fieldsValidator;
    }
    
    protected function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
