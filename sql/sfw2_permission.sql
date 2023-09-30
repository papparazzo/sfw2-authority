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

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}_permission` (
    `RoleId` int(10) UNSIGNED NOT NULL,
    `PathId` int(10) UNSIGNED NOT NULL,
    `Permission` set('READ_OWN','READ_ALL','CREATE','UPDATE_OWN','UPDATE_ALL','DELETE_OWN','DELETE_ALL') NOT NULL DEFAULT 'READ_OWN'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `{TABLE_PREFIX}_permission` ADD UNIQUE KEY `RoleId` (`RoleId`,`PathId`);

