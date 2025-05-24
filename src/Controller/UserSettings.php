<?php

/*
 *  Project:    sfw2-authority
 *
 *  Copyright (C) 2019 Stefan Paproth <pappi-@gmx.de>
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

namespace SFW2\Authorization\Controller;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SFW2\Database\DatabaseInterface;
use SFW2\Render\RenderInterface;

use SFW2\Authority\User;
use SFW2\Authority\Helper\LoginHelperTrait;

use SFW2\Session\SessionInterface;

final class UserSettings {

    use LoginHelperTrait;

    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly SessionInterface $session,
        private readonly RenderInterface $render,
    ) {
    }

    /**
     * @throws Exception
     */
    public function index(Request $request, Response $response): Response
    {
        if(isset($request->getQueryParams()['getForm'])) {
            return $this->render->render(
                $request,
                $response,
                $this->getRow(),
                "SFW2\\Authorization\\UserSettings\\UserSettings"
            );
        }

        return $this->render->render(
            $request,
            $response,
            $this->getEntries(),
            template: "SFW2\\Authorization\\UserSettings\\Users"
        );

















    }

    /**
     * @throws Exception
     */
    protected function getEntries(): array
    {
        $stmt = /** @lang MySQL */
            "SELECT `Id`, `Active`, `Admin`, `FirstName`, `LastName`, `Sex`, `Birthday`, `Email`, `Phone`, `Street`, " .
            "`HouseNumber`, `PostalCode`, `City` " .
            "FROM `{TABLE_PREFIX}_authority_user`";

        $rows = $this->database->select($stmt);

        $entries = [];

        $deleteAllowed = true; #  $this->permission->checkPermission($pathId, 'delete');

        foreach($rows as $row) {
           # $entry = [];
           # $entry['delete_allowed'] = true; # $deleteAllowed !== AccessType::FORBIDDEN;
           # $entries[] = $entry;
        }

        return [
            'entries' => $rows,
            'create_allowed' => true #$this->permission->checkPermission($pathId, 'create') !== AccessType::FORBIDDEN
        ];
    }


    protected function getRow(): array
    {
        $stmt = /** @lang MySQL */
            "SELECT `Id`, `Active`, `Admin`, `FirstName`, `LastName`, `Sex`, `Birthday`, `Email`, `Phone`, `Street`, " .
            "`HouseNumber`, `PostalCode`, `City` " .
            "FROM `{TABLE_PREFIX}_authority_user` " .
            "WHERE Id = %s";


        $userId = $this->session->getGlobalEntry(User::class);
        return $this->database->selectRow($stmt, [$userId]);
    }










#INSERT INTO sfw2.`{TABLE_PREFIX}_authority_user` (Id, Active, Admin, FirstName, LastName, Sex, Birthday, Email, Phone, Street, HouseNumber, PostalCode, City, Password, Retries, LastTry, ResetExpireDate, ResetHash) VALUES (1, 1, 0, 'Stefan', 'Paproth', 'MALE', '2023-12-21', 'stefan.paproth@springer-singgemeinschaft.de', 'null', '', '', '', '', '$2y$10$4SR1RIFLQNPVa1NWhrKO3uhvEoTI6/zYlRuGSDsnIbO1A4ya0RtNa', 0, '2024-02-04 14:14:03', null, '');







}
