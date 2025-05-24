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

namespace SFW2\Authorization\Permission;

use SFW2\Core\Permission\AccessType;
use SFW2\Core\Permission\PermissionInterface;
use SFW2\Database\DatabaseException;
use SFW2\Database\DatabaseInterface;
use SFW2\Database\QueryHelper;
use SFW2\Interoperability\User\UserEntity;
use SFW2\Interoperability\User\UserRepositoryInterface;
use SFW2\Session\SessionInterface;

final class Permission implements PermissionInterface
{
    private bool $isAdmin;

    private array $permissions = [];

    /**
     * @throws DatabaseException
     */
    public function __construct(
        SessionInterface $session,
        private readonly DatabaseInterface $database,
        private readonly UserRepositoryInterface $userRepository
    ) {
        $userId = $session->getEntry(UserEntity::class);

        $this->isAdmin = $this->userRepository->loadUserById($userId)->isAdmin();

        if ($this->isAdmin) {
            return;
        }

        $roles = $this->getRoles($userId);
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
     * @throws DatabaseException
     */
    private function loadPermissions(int $parentPathId, $initPermission, array $roles): void
    {
        $stmt = /** @lang MySQL */
            "SELECT `Id` " .
            "FROM `{TABLE_PREFIX}_path` " .
            "WHERE `ParentPathId` = %s";

        $rows = $this->database->select($stmt, [$parentPathId]);

        $queryHelper = new QueryHelper($this->database);

        foreach ($rows as $row) {
             $subRow = $queryHelper->selectKeyValue(
                'Action',
                'Access',
                '{TABLE_PREFIX}_authority_permission',
                ['PathId' => $row['Id'], 'RoleId' => $roles]
            );
            $permission = array_merge($initPermission, $subRow);
            $this->permissions[$row['Id']] = $permission;
            $this->loadPermissions($row['Id'], $permission, $roles);
        }
    }

    public function checkPermission(int $pathId, string $action): AccessType
    {
        if ($this->isAdmin) {
            return AccessType::FULL;
        }

        $rv = AccessType::FORBIDDEN;

        foreach ($this->permissions[$pathId] as $k => $v) {
            if($k == '*') {
                $rv = AccessType::getByName($v);
            }
        }

        foreach ($this->permissions[$pathId] as $k => $v) {
            if($k == $action) {
                return AccessType::getByName($v);
            }
        }

        return $rv;
    }
}
