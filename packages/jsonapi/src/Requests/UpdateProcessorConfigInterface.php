<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\OutputHandling\PropertyReadableTypeProviderInterface;
use EDT\JsonApi\OutputHandling\ResponseFactory;
use EDT\JsonApi\RequestHandling\RequestConstraintFactory;
use EDT\JsonApi\RequestHandling\RequestWithBody;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Instances must provide logic and implementations needed by {@link UpdateProcessor} instances.
 */
interface UpdateProcessorConfigInterface
{
    public function getRequestConstraintFactory(): RequestConstraintFactory;

    public function getEventDispatcher(): EventDispatcherInterface;

    public function getResponseFactory(PropertyReadableTypeProviderInterface $typeProvider): ResponseFactory;

    public function getValidator(): ValidatorInterface;

    /**
     * @return int<1,8192> see {@link RequestWithBody::getRequestBody()}
     */
    public function getMaxBodyNestingDepth(): int;
}
