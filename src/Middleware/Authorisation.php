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

// TODO Rename into AuthorisationMiddleware
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
        $pathId = $this->getPathId($request);
        $action = $this->getAction($request);

        if(!$this->permission->getActionPermission($pathId, $action)) {
            throw new HttpForbidden();
        }

        $permissions = $this->permission->getPagePermission($pathId)->getPermissions();
        $data = PagePermissionType::getPermissionArray($permissions);

       # $data['user_id'] = 1;
       # $data['authenticated'] = false;
       # $data['user_name'] = 'Hans Hanselmann';

        $request = $request->withAttribute('sfw2_authority', $data);

        return $handler->handle($request);
    }
}