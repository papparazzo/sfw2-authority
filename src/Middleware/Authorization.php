<?php

/**
 *  SFW2 - SimpleFrameWork
 *
 *  Copyright (C) 2025 Stefan Paproth
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <https://www.gnu.org/licenses/agpl.txt>.
 *
 */

namespace SFW2\Authorization\Middleware;

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

final class Authorization implements MiddlewareInterface
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