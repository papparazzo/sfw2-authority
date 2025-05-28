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

use SFW2\Exception\HttpExceptions\Status4xx\HttpStatus403Forbidden;
use SFW2\Interoperability\Path\MethodType;
use SFW2\Interoperability\Path\Path;
use SFW2\Interoperability\Path\PathMapInterface;
use SFW2\Interoperability\PermissionInterface;

final class Authorization implements MiddlewareInterface
{
    public function __construct(
        private readonly PermissionInterface $permission,
        private readonly PathMapInterface    $pathMap,
    ) {
    }

    /**
     * @throws HttpStatus403Forbidden
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pathId = $this->pathMap->getPathId(Path::createFromRequest($request));

        if(!$this->permission->hasPermission($pathId, MethodType::from($request->getMethod()))) {
            throw new HttpStatus403Forbidden();
        }

        return $handler->handle($request);
    }
}
