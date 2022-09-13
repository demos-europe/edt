<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\Querying\Utilities\Iterables;
use EDT\Wrapping\Contracts\Types\ReadableTypeInterface;
use EDT\Wrapping\Contracts\WrapperFactoryInterface;
use EDT\Wrapping\WrapperFactories\WrapperObject;
use League\Fractal\ParamBag;

/**
 * Wraps a given callable so that its return is transformed when the
 * {@link TransformerObjectWrapper::__invoke()} is called.
 *
 * Normally the wrapping is done automatically in {@link PropertyDefinition::determineData()}
 * because it utilizes a {@link WrapperObject} that has the logic of this method
 * included. But for a custom read callable we must do the wrapping manually with
 * the callable returned by this method.
 *
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 * @template O of object
 */
class TransformerObjectWrapper
{
    /**
     * @var callable(O, ParamBag): (O|iterable<O>|null)
     */
    private $callable;

    /**
     * @var ReadableTypeInterface<C, S, O>
     */
    private $relationshipType;

    /**
     * @var WrapperFactoryInterface<C, S, O, WrapperObject<O>>
     */
    private $wrapperFactory;

    /**
     * @param callable(O, ParamBag): (O|iterable<O>|null)      $callable
     * @param ReadableTypeInterface<C, S, O>                      $relationshipType
     * @param WrapperFactoryInterface<C, S, O, WrapperObject<O>>  $wrapperFactory
     */
    public function __construct(callable $callable, ReadableTypeInterface $relationshipType, WrapperFactoryInterface $wrapperFactory)
    {
        $this->callable = $callable;
        $this->relationshipType = $relationshipType;
        $this->wrapperFactory = $wrapperFactory;
    }

    /**
     * Will execute {@link TransformerObjectWrapper::$callable} and wrap the result(s)
     * using {@link TransformerObjectWrapper::$wrapperFactory}. Because this method is
     * only intended (and needed) for relationships we expect the result of @link TransformerObjectWrapper::$callable}
     * to be either `null`, an `object`, or an iterable of `object`s. If something
     * else is returned by it, then the behavior of this method is undefined.
     *
     * @param O $entity
     *
     * @return WrapperObject<O>|list<WrapperObject<O>>|null
     */
    public function __invoke(object $entity, ParamBag $params)
    {
        $rawResult = ($this->callable)($entity, $params);
        if (null === $rawResult) {
            return null;
        }

        if (is_iterable($rawResult)) {
            return array_map([$this, 'wrapSingle'], array_values(Iterables::asArray($rawResult)));
        }

        return $this->wrapSingle($rawResult);
    }

    /**
     * @param O $relationship
     *
     * @return WrapperObject<O>
     */
    private function wrapSingle(object $relationship): WrapperObject
    {
        return $this->wrapperFactory->createWrapper($relationship, $this->relationshipType);
    }
}
