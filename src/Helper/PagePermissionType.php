<?php

/**
 *  SFW2 - SimpleFrameWork
 *
 *  Copyright (C) 2023  Stefan Paproth
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

declare(strict_types=1);

namespace SFW2\Authority\Helper;

enum PagePermissionType: int {
    case NO_PERMISSION = 0;
    case READ_OWN      = 1;
    case READ_ALL      = 2;
    case CREATE        = 4;
    case UPDATE_OWN    = 8;
    case UPDATE_ALL    = 16;
    case DELETE_OWN    = 32;
    case DELETE_ALL    = 64;

    public static function getAll(): int {
        $all = 0;
        foreach(PagePermissionType::cases() as $permission) {
            $all |= $permission->value;
        }
        return $all;
    }

    public static function getPermissionArray(int $permission): array {
        $result = [];
        foreach(PagePermissionType::cases() as $item) {
            $result[strtolower($item->name)] = (bool)($permission & $item->value);
        }
        return $result;
    }
}

