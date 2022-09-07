<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use InvalidArgumentException;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use League\Fractal\Pagination\PaginatorInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class PaginatorFactory
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RequestStack $requestStack, RouterInterface $router)
    {
        $request = $requestStack->getCurrentRequest();
        if (null === $request) {
            throw new InvalidArgumentException('No request available in request stack.');
        }

        $this->request = $request;
        $this->router = $router;
    }

    public function createPaginatorAdapter(Pagerfanta $paginator): PaginatorInterface
    {
        return new PagerfantaPaginatorAdapter(
            $paginator,
            function ($page) {
                $route = $this->request->attributes->get('_route');
                $inputParams = $this->request->attributes->get('_route_params');
                $newParams = array_merge($inputParams, $this->request->query->all());
                $newParams['page'] = $page;

                return $this->router->generate($route, $newParams, 0);
            }
        );
    }
}
