<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use League\Fractal\Pagination\PaginatorInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class PaginatorFactory
{
    public function __construct(
        protected readonly RouterInterface $router
    ) {}

    /**
     * @param Pagerfanta<object> $paginator
     */
    public function createPaginatorAdapter(Pagerfanta $paginator, Request $request): PaginatorInterface
    {
        return new PagerfantaPaginatorAdapter(
            $paginator,
            function (int $page) use ($request): string {
                $route = $request->attributes->get('_route');
                Assert::stringNotEmpty($route);
                $inputParams = $request->attributes->get('_route_params');
                Assert::isArray($inputParams);
                $newParams = array_merge($inputParams, $request->query->all());
                $newParams[UrlParameter::PAGE] = $page;

                return $this->router->generate($route, $newParams, 0);
            }
        );
    }
}
