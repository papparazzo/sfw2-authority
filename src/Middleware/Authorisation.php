<?php

namespace SFW2\Authority\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use SFW2\Authority\Helper\PagePermissionType;
use SFW2\Authority\Permission\PermissionInterface;
use SFW2\Core\HttpExceptions\HttpForbidden;
use SFW2\Routing\HelperTraits\getRoutingDataTrait;

class Authorisation implements MiddlewareInterface
{
    use getRoutingDataTrait;

    public function __construct(
        private readonly PermissionInterface $permission
    )
    {
    }

    /**
     * @throws HttpForbidden
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // TODO set "own / all" for crud operations in header

        if(!$this->permission->getActionPermission($pathId, $action)) {
            throw new HttpForbidden();
        }

        return $handler->handle($request);
    }
}