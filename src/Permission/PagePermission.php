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

use SFW2\Authority\Helper\PagePermissionType;

class PagePermission {

    protected int $permissions;

    public function __construct(array $permissions = [])
    {
        $this->setPermissions($permissions);
    }

    public function setAllPermissions(): PagePermission
    {
        $this->permissions = PagePermissionType::getAll();
        return $this;
    }

    public function setPermissions(array $permissions): PagePermission
    {
        $this->permissions = PagePermissionType::NO_PERMISSION->value;

        foreach($permissions as $permission) {
            $this->permissions |= PagePermissionType::from($permission)->value;
        }
        return $this;
    }

    public function getPermissions(): int
    {
        return $this->permissions;
    }

    public function readOwnAllowed() : bool {
        return (bool)($this->permissions & PagePermissionType::READ_OWN->value);
    }

    public function readAllAllowed() : bool {
        return (bool)($this->permissions & PagePermissionType::READ_ALL->value);
    }

    public function createAllowed() : bool {
        return (bool)($this->permissions & PagePermissionType::CREATE->value);
    }

    public function updateOwnAllowed() : bool {
        return (bool)($this->permissions & PagePermissionType::UPDATE_OWN->value);
    }

    public function updateAllAllowed() : bool {
        return (bool)($this->permissions & PagePermissionType::UPDATE_ALL->value);
    }

    public function deleteOwnAllowed() : bool {
        return (bool)($this->permissions & PagePermissionType::DELETE_OWN->value);
    }

    public function deleteAllAllowed() : bool {
        return (bool)($this->permissions & PagePermissionType::DELETE_ALL->value);
    }
}
