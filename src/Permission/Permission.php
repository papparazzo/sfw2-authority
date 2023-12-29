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

use InvalidArgumentException;
use SFW2\Database\DatabaseInterface;
use SFW2\Authority\User;

class Permission implements PermissionInterface {

    protected bool $isAdmin = false;

    protected array $permissions = [];

    protected array $roles = [];

    /**
     * @throws PermissionException
     */
    public function __construct(protected readonly DatabaseInterface $database, ?User $user = null) {
        if (is_null($user)) {
            $this->isAdmin = true;
            return;
        }

        $this->isAdmin = $user->isAdmin();
        $this->loadRoles($user->getUserId());
        $this->loadPermissions(0, $this->getInitPermission());
    }

    /**
     * @throws PermissionException
     */
    protected function loadRoles(int $userId): void
    {
        $stmt = "SELECT `RoleId` FROM `{TABLE_PREFIX}_user_role` WHERE `UserId` = %s";

        $rows = $this->database->select($stmt, []);
        foreach($rows as $row) {
            $this->roles[] = $row['RoleId'];
        }

        if(empty($this->roles)) {
            throw new PermissionException('No roles defines', PermissionException::NO_ROLES_DEFINED);
        }
    }

    protected function getInitPermission()
    {
        $stmt = /** @lang MySQL */
            "SELECT GROUP_CONCAT(`Permission`) AS `Permission` " .
            "FROM `{TABLE_PREFIX}_authority_permission` " .
            "WHERE `PathId` = '0' " .
            "AND `RoleId` IN(%s) ";

        $permission = $this->database->selectSingle($stmt, [implode(',', $this->roles)]);
        $this->permissions[0] = new PagePermission(explode(',', $permission));
        return $permission;
    }

    protected function loadPermissions(int $parentPathId, $initPermission): void {
        if ($this->isAdmin) {
            return;
        }

        $stmt = /** @lang MySQL */
            "SELECT `Id` " .
            "FROM `{TABLE_PREFIX}_path` " .
            "WHERE `ParentPathId` = %s";

        $rows = $this->database->select($stmt, [$parentPathId]);

        foreach ($rows as $row) {
            $stmt = /** @lang MySQL */
                "SELECT GROUP_CONCAT(`Permission`) AS `Permission` " .
                "FROM `{TABLE_PREFIX}_authority_permission` " .
                "WHERE `PathId` = %s " .
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

    public function getPagePermission(int $pathId): PagePermission {
        if($this->isAdmin) {
            return (new PagePermission())->setAllPermissions();
        }

        if(!isset($this->permissions[$pathId])) {
            return new PagePermission();
        }
        return $this->permissions[$pathId];
    }

    public function getActionPermission(int $pathId, $action = 'index'): bool {
        if($this->isAdmin) {
            return true;
        }

        return match ($action) {
            'create' => $this->getPagePermission($pathId)->createAllowed(),
            'update' => $this->getPagePermission($pathId)->updateOwnAllowed(),
            'delete' => $this->getPagePermission($pathId)->deleteOwnAllowed(),
            default => $this->getPagePermission($pathId)->readOwnAllowed(),
        };
    }

    public function hasFullActionPermission($pathId, $action = 'index'): bool {
        if($this->isAdmin) {
            return true;
        }

        return match ($action) {
            'create' => $this->getPagePermission($pathId)->createAllowed(),
            'update' => $this->getPagePermission($pathId)->updateAllAllowed(),
            'delete' => $this->getPagePermission($pathId)->deleteAllAllowed(),
            default => $this->getPagePermission($pathId)->readAllAllowed(),
        };
    }

    public function getPermission(int $pathId, string $action): int
    {
       throw new InvalidArgumentException("gibt es nicht");
    }
}
