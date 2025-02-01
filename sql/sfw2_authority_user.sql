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

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}_authority_user` (
    `Id` INT(11) UNSIGNED NOT NULL,
    `Active` BOOLEAN NOT NULL DEFAULT '1',
    `Admin` BOOLEAN NOT NULL DEFAULT '0',
    `FirstName` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
    `LastName` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
--    `Sex` ENUM('FEMALE','MALE','DIVERSE') COLLATE utf8_unicode_ci DEFAULT NULL,
--    `Birthday` DATE DEFAULT NULL,
    `Email` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
    `Phone` VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
    `Street` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
    `HouseNumber` VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
    `PostalCode` VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
    `City` VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
    `Password` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
    `Retries` TINYINT(11) NOT NULL DEFAULT 0,
    `LastTry` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ResetExpireDate` datetime DEFAULT NULL,
    `ResetHash` VARCHAR(32) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `{TABLE_PREFIX}_authority_user` ADD PRIMARY KEY (`Id`);
ALTER TABLE `{TABLE_PREFIX}_authority_user` MODIFY `Id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;
