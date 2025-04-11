<?php

namespace SFW2\Authority\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use SFW2\Authority\User;
use SFW2\Core\HttpExceptions\HttpForbidden;
use SFW2\Core\HttpExceptions\HttpNotFound;
use SFW2\Core\Permission\AccessType;
use SFW2\Core\Permission\PermissionInterface;
use SFW2\Database\DatabaseException;
use SFW2\Database\DatabaseInterface;
use SFW2\Routing\HelperTraits\getRoutingDataTrait;
use SFW2\Session\SessionInterface;
use SFW2\Validator\Exception;

class Authorisation implements MiddlewareInterface
{
    use getRoutingDataTrait;

    public function __construct(
        private readonly PermissionInterface $permission,
        private readonly SessionInterface    $session,
        private readonly DatabaseInterface   $database
    ) {
    }

    /**
     * @throws HttpForbidden|HttpNotFound
     * @throws DatabaseException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pathId = $this->getPathId($request);
        $action = $this->getAction($request);

        $permission = $this->permission->checkPermission($pathId, $action);

        if ($permission === AccessType::FORBIDDEN) {
            throw new HttpForbidden();
        }

        $userId = $this->session->getGlobalEntry(User::class);
        $user = (new User($this->database))->loadUserById($userId);

        $data['user_id'      ] = $user->getUserId();
        $data['authenticated'] = $user->isAuthenticated();
        $data['user_name'    ] = $user->getFullName();
        $data['restricted'   ] = $permission === AccessType::RESTRICTED;

        $request = $request->withAttribute('sfw2_authority', $data);

        return $handler->handle($request);
    }
}