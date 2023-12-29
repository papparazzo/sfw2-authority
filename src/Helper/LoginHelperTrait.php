<?php

/**
 *  SFW2 - SimpleFrameWork
 *
 *  Copyright (C) 2019  Stefan Paproth
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

namespace SFW2\Authority\Helper;

trait LoginHelperTrait {

    protected static int $EXPIRE_DATE_OFFSET = 86400; #24 * 60 * 60;

    protected function getExpireDate($date): string {
        return intval($date / 60 / 60) . ' Stunden';
    }
}
