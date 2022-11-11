<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\Querying\Utilities\Iterables;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\WrapperFactories\WrapperObject;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
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
 * @template TCondition of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @template TSorting of \EDT\Querying\Contracts\SortMethodInterface
 * @template TEntity of object
 * @template TRelationship of object
 */
class TransformerObjectWrapper
{
    /**
     * @var callable(TEntity, ParamBag): (TRelationship|iterable<TRelationship>|null)
     */
    private $callable;

    /**
     * @var TransferableTypeInterface<TCondition, TSorting, TRelationship>
     */
    private TransferableTypeInterface $relationshipType;

    private WrapperObjectFactory $wrapperFactory;

    /**
     * @param callable(TEntity, ParamBag): (TRelationship|iterable<TRelationship>|null) $callable
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship>            $relationshipType
     */
    public function __construct(callable $callable, TransferableTypeInterface $relationshipType, WrapperObjectFactory $wrapperFactory)
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
     * @param TEntity $entity
     *
     * @return WrapperObject<TRelationship>|list<WrapperObject<TRelationship>>|null
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
     * @param TRelationship $relationship
     *
     * @return WrapperObject<TRelationship>
     */
    private function wrapSingle(object $relationship): WrapperObject
    {
        return $this->wrapperFactory->createWrapper($relationship, $this->relationshipType);
    }
}
