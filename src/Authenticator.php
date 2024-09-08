<?php

/**
 *  SFW2 - SimpleFrameWork
 *
 *  Copyright (C) 2024  Stefan Paproth
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

namespace SFW2\Authority;

use DateTime;
use Exception;
use SensitiveParameter;
use SFW2\Database\DatabaseException;
use SFW2\Database\DatabaseInterface;
use SFW2\Database\QueryHelper;

class Authenticator
{
    private const MAX_RETRIES = 100;

    public function __construct(protected readonly DatabaseInterface $database)
    {
    }

    /**
     * @throws DatabaseException
     */
    public function authenticateUser(string $emailAddr, #[SensitiveParameter] string $pwd): User
    {
        $stmt = /** @lang MySQL */
            "SELECT `Id`, `FirstName`, `LastName`, `Email`, `Password`, `Admin`, " .
            "IF(CURRENT_TIMESTAMP > `LastTry` + POW(2, `Retries`) - 1, 1, 0) AS `OnTime` " .
            "FROM `{TABLE_PREFIX}_authority_user` " .
            "WHERE `Email` LIKE %s " .
            "AND `Active` = '1'";

        $queryHelper = new QueryHelper($this->database);
        $row = $queryHelper->selectRow($stmt, [$emailAddr]);

        if (empty($row)) {
            return new User($this->database);
        }

        if ($row['OnTime'] == 0) {
            return new User($this->database);
        }

        if (!$this->checkPassword($row['Id'], $row['Password'], $pwd)) {
            $this->updateRetries($row['Id'], false);
            return new User($this->database);
        }

        $this->updateRetries($row['Id'], true);
        return (new User($this->database))->fill($row);
    }

    /**
     * @throws Exception
     */
    public function getHash(string $emailAddr, DateTime $expireDate): ?string
    {
        $hash = uniqid(more_entropy: true);

        $stmt = /** @lang MySQL */
            "UPDATE `{TABLE_PREFIX}_authority_user` " .
            "SET `ResetExpireDate` = %s, `ResetHash` = %s " .
            "WHERE `Email` = %s ";

        $val = $this->database->update($stmt, [$expireDate, $hash, $emailAddr]);

        if($val !== 1) {
            return null;
        }

        return $hash;
    }

    /**
     * @throws DatabaseException
     */
    public function resetPasswordByUser(
        int $userId, #[SensitiveParameter] string $oldPwd, #[SensitiveParameter] string $newPwd
    ): bool
    {
        $stmt = /** @lang MySQL */
            "SELECT `Password` " .
            "FROM `{TABLE_PREFIX}_authority_user` " .
            "WHERE `Id` = %s";

        $queryHelper = new QueryHelper($this->database);
        $oldPwdHash = $queryHelper->selectSingle($stmt, [$userId]);

        if (!$this->checkPassword($userId, $oldPwdHash, $oldPwd)) {
            return false;
        }
        return $this->resetPassword($userId, $newPwd);
    }

    /**
     * @throws DatabaseException
     */
    public function resetPasswordByHash(string $hash, #[SensitiveParameter] string $newPwd): bool
    {
         $stmt = /** @lang MySQL */
            "SELECT `Id` " .
            "FROM `{TABLE_PREFIX}_authority_user` " .
            "WHERE `ResetExpireDate` >= NOW() " .
            "AND `ResetHash` = %s";

        $queryHelper = new QueryHelper($this->database);
        $row = $queryHelper->selectRow($stmt, [$hash]);

        if (empty($row)) {
            return false;
        }

        return $this->resetPassword($row['Id'], $newPwd);
    }

    /**
     * @throws DatabaseException
     */
    private function resetPassword(int $userId, #[SensitiveParameter] string $newPwd): bool
    {
        $stmt = /** @lang MySQL */
            "UPDATE `{TABLE_PREFIX}_authority_user` " .
            "SET `Password` = %s, `Retries` = 0, `ResetExpireDate` = NULL, `ResetHash` = '' " .
            "WHERE `Id` = %s";

        $newHash = password_hash($newPwd, PASSWORD_DEFAULT);
        $cnt = $this->database->update($stmt, [$newHash, $userId]);
        return $cnt == 1;
    }

    /**
     * @throws DatabaseException
     */
    private function updateRetries(int $loginId, bool $sucess): void
    {
        $stmt = "UPDATE `{TABLE_PREFIX}_authority_user` ";
        if ($sucess) {
            $stmt .= "SET `Retries` = 0, `ResetExpireDate` = NULL, `ResetHash` = '' ";
        } else {
            $stmt .=
                "SET `Active` = IF(`Retries` + 1 < " . self::MAX_RETRIES . ", 1, 0), " .
                "`Retries` = IF(`Retries` + 1 < " . self::MAX_RETRIES . ", `Retries` + 1, 0) ";
        }
        $stmt .=
            "WHERE `Id` = %s " .
            "AND `Active` = 1 " .
            "AND CURRENT_TIMESTAMP > `LastTry` +  POW(2, `Retries`) - 1";

        $this->database->update($stmt, [$loginId]);
    }

    /**
     * @throws DatabaseException
     */
    private function checkPassword(int $userId, string $hash, #[SensitiveParameter] string $password): bool
    {
        if (!password_verify($password, $hash)) {
            return false;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $stmt = "UPDATE `{TABLE_PREFIX}_authority_user` SET `Password` = %s WHERE `Id` = %s ";
            $newh = password_hash($password, PASSWORD_DEFAULT);
            $this->database->update($stmt, [$newh, $userId]);
        }
        return true;
    }
}