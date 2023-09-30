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

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}_user` (
    `Id` int(11) UNSIGNED NOT NULL,
    `Active` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
    `Admin` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
    `FirstName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
    `LastName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
    `Sex` enum('FEMALE','MALE','UNKNOWN') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNKNOWN',
    `Birthday` date DEFAULT NULL,
    `Email` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
    `Phone1` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
    `Phone2` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
    `LoginName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
    `Password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `Retries` tinyint(11) NOT NULL,
    `LastTry` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ResetExpireDate` datetime NOT NULL,
    `ResetHash` varchar(32) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `{TABLE_PREFIX}_user` ADD PRIMARY KEY (`Id`);
ALTER TABLE `{TABLE_PREFIX}_user` MODIFY `Id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
