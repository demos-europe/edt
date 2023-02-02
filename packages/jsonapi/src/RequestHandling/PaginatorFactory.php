<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use InvalidArgumentException;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use League\Fractal\Pagination\PaginatorInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class PaginatorFactory
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router
    ) {}

    public function createPaginatorAdapter(Pagerfanta $paginator): PaginatorInterface
    {
        return new PagerfantaPaginatorAdapter(
            $paginator,
            function ($page) {
                $request = $this->requestStack->getCurrentRequest();
                if (null === $request) {
                    throw new InvalidArgumentException('No request available in request stack.');
                }
                $route = $request->attributes->get('_route');
                $inputParams = $request->attributes->get('_route_params');
                $newParams = array_merge($inputParams, $request->query->all());
                $newParams[UrlParameter::PAGE] = $page;

                return $this->router->generate($route, $newParams, 0);
            }
        );
    }
}
