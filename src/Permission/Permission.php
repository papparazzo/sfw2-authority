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

namespace SFW2\Authorization\Permission;

use SFW2\Core\Permission\AccessType;
use SFW2\Core\Permission\PermissionInterface;
use SFW2\Database\DatabaseException;
use SFW2\Database\DatabaseInterface;
use SFW2\Database\QueryHelper;
use SFW2\Interoperability\Path\MethodType;
use SFW2\Interoperability\Path\PathTreeInterface;
use SFW2\Interoperability\PermissionInterface;
use SFW2\Interoperability\User\UserEntity;

final class Permission implements PermissionInterface
{
    private bool $isAdmin;

    private array $permissions = [];

    /**
     * @throws DatabaseException
     */
    public function __construct(
        readonly UserEntity $user,
        private readonly DatabaseInterface $database,
        private readonly PathTreeInterface $pathTree,
    ) {
        $this->isAdmin = $user->isAdmin();

        if ($this->isAdmin) {
            return;
        }

        $roles = $this->getRoles($user->getUserId());
        $this->loadPermissions(0, $this->getInitPermission($roles), $roles);
    }

    /**
     * @throws DatabaseException
     */
    private function getRoles(?int $userId): array
    {
        if (is_null($userId)) {
            $stmt = "SELECT `RoleId` FROM `{TABLE_PREFIX}_authority_user_role` WHERE `UserId` IS NULL";
            $rows = $this->database->select($stmt);
        } else {
            $stmt = "SELECT `RoleId` FROM `{TABLE_PREFIX}_authority_user_role` WHERE `UserId` = %s";
            $rows = $this->database->select($stmt, [$userId]);
        }

        $roles = [];
        foreach ($rows as $row) {
            $roles[] = $row['RoleId'];
        }
        return $roles;
    }

    /**
     * @param int[] $roles
     * @return array<string, bool>
     * @throws DatabaseException
     */
    private function getInitPermission(array $roles): array
    {
        $queryHelper = new QueryHelper($this->database);
        return
            $this->permissions[0] = $queryHelper->selectKeyValue(
                'Action',
                'Access',
                '{TABLE_PREFIX}_authority_permission',
                ['PathId' => 0, 'RoleId' => $roles]
            );
    }

    /**
     * @param int $parentPathId
     * @param array<string, bool> $initPermission
     * @param int[] $roles
     * @return void
     * @throws DatabaseException
     */
    private function loadPermissions(int $parentPathId, array $initPermission, array $roles): void
    {
        $children = $this->pathTree->getChildren($parentPathId);

        $queryHelper = new QueryHelper($this->database);

        foreach ($children as $pathId) {
             $subRow = $queryHelper->selectKeyValue(
                'Action',
                'Access',
                '{TABLE_PREFIX}_authority_permission',
                ['PathId' => $pathId, 'RoleId' => $roles]
            );
            $permission = array_merge($initPermission, $subRow);
            $this->permissions[$pathId] = $permission;
            $this->loadPermissions($pathId, $permission, $roles);
        }
    }

    public function hasPermission(int $pathId, MethodType $method = MethodType::GET): bool
    {
        if ($this->isAdmin) {
            return true;
        }

        $rv = false;

        foreach ($this->permissions[$pathId] as $k => $v) {
            if($k == 'ANY') {
                $rv = (bool)$v;
            }
        }

        foreach ($this->permissions[$pathId] as $k => $v) {
            if($k == $method) {
                return (bool)$v;
            }
        }

        return $rv;
    }
}
