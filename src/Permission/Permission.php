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

use SFW2\Core\Database;
use SFW2\Core\Permission\PermissionInterface;
use SFW2\Core\Permission\PagePermission;
use SFW2\Authority\User;

class Permission implements PermissionInterface {

    protected $permission = [];

    /**
     * @var \SFW2\Core\Database
     */
    protected $database = null;

    /**
     * @var \SFW2\Authority\User
     */
    protected $user = null;

    /**
     * @var Array
     */
    protected $permissions = [];

    /**
     * @var Array
     */
    protected $roles = [];

    public function __construct(Database $database, User $user) {
        $this->user = $user;
        $this->database = $database;
        if($this->user->isAdmin()) {
            return;
        }
        $this->loadRoles();
        $this->loadPermissions(0, $this->getInitPermission());
    }

    protected function loadRoles() {
        if($this->user->isAdmin()) {
            return;
        }

        $stmt =
            "SELECT `RoleId` " .
            "FROM `sfw2_user_role` " .
            "WHERE `UserId` = '%s'";

        $rows = $this->database->select($stmt, [$this->user->getUserId()]);
        foreach($rows as $row) {
            $this->roles[] = $row['RoleId'];
        }

        if(empty($this->roles)) {
            throw new PermissionException('No roles defines', PermissionException::NO_ROLES_DEFINED);
        }
    }

    protected function getInitPermission() {
        $stmt =
           "SELECT GROUP_CONCAT(`Permission`) AS `Permission` " .
           "FROM `sfw2_permission` " .
           "WHERE `PathId` = '0' " .
           "AND `RoleId` IN(%s) " .
           "GROUP BY `RoleId`";

        $permission = $this->database->selectSingle($stmt, [implode(',', $this->roles)]);
        $this->permissions[0] = new PagePermission(explode(',', $permission));
        return $permission;
    }

    protected function loadPermissions(int $parentPathId, $initPermission) {
        if($this->user->isAdmin()) {
            return;
        }

        $stmt =
            "SELECT `sfw2_path`.`Id` " .
            "FROM `sfw2_path` " .
            "WHERE `ParentPathId` = '%s'";

        $rows = $this->database->select($stmt, [$parentPathId]);

        foreach($rows as $row) {
            $stmt =
                "SELECT GROUP_CONCAT(`Permission`) AS `Permission` " .
                "FROM `sfw2_permission` " .
                "WHERE `PathId` = '%s' " .
                "AND `RoleId` IN(%s) " .
                "GROUP BY `RoleId`";

            $subRow = $this->database->selectRow($stmt, [$row['Id'], implode(',', $this->roles)]);

            $permission = $initPermission;
            if(!empty($subRow)) {
                $permission = $subRow['Permission'];
            }

            $this->permissions[$row['Id']] = new PagePermission(explode(',', $permission));
            $this->loadPermissions($row['Id'], $permission);
        }
    }

    public function getPagePermission($pathId) : PagePermission {
        if($this->user->isAdmin()) {
            return (new PagePermission())->setAllPermissions();
        }

        if(!isset($this->permissions[$pathId])) {
            return new PagePermission();
        }
        return $this->permissions[$pathId];
    }

    public function getActionPermission($pathId, $action = 'index') : bool {
        if($this->user->isAdmin()) {
            return true;
        }

        switch($action) {
            case 'create':
                return $this->getPagePermission($pathId)->createAllowed();

            case 'update':
                return $this->getPagePermission($pathId)->updateOwnAllowed();

            case 'delete':
                return $this->getPagePermission($pathId)->deleteOwnAllowed();

            case 'index':
            default:
                return $this->getPagePermission($pathId)->readOwnAllowed();
        }
    }

    public function hasFullActionPermission($pathId, $action = 'index') : bool {
        if($this->user->isAdmin()) {
            return true;
        }

        switch($action) {
            case 'create':
                return $this->getPagePermission($pathId)->createAllowed();

            case 'update':
                return $this->getPagePermission($pathId)->updateAllAllowed();

            case 'delete':
                return $this->getPagePermission($pathId)->deleteAllAllowed();

            case 'index':
            default:
                return $this->getPagePermission($pathId)->readAllAllowed();
        }
    }
}
