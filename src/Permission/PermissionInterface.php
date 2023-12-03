<?php

/**
 *  SFW2 - SimpleFrameWork
 *
 *  Copyright (C) 2018  Stefan Paproth
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
 *  along with this program. If not, see <http://www.gnu.org/licenses/agpl.txt>.
 *
 */

namespace SFW2\Authority\Permission;

interface PermissionInterface {
    final public const VORBIDDEN  = 0;
    final public const FULL       = 1;
    final public const RESTRICTED = 2;

    public function getPagePermission(int $pathId): PagePermission;

    public function getActionPermission(int $pathId, string $action = 'index'): bool;

    public function hasFullActionPermission(int $pathId, string $action = 'index'): bool;

    public function getPermission(int $pathId, string $action): int;

}