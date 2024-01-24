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

namespace SFW2\Authority\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SFW2\Routing\AbstractController;
use SFW2\Routing\ResponseEngine;
use SFW2\Routing\Result\Content;
use SFW2\Core\Database;

use SFW2\Authority\User;
use SFW2\Authority\Helper\LoginHelperTrait;

use SFW2\Validator\Ruleset;
use SFW2\Validator\Validator;
use SFW2\Validator\Validators\IsNotEmpty;
use SFW2\Validator\Validators\IsSameAs;

use SFW2\Core\Session;

class UserSettings extends AbstractController {

    use LoginHelperTrait;

    /**
     * @var \SFW2\Routing\User
     */
    protected $user;

    /**
     * @var \SFW2\Core\Session
     */
    protected $session;

    /**
     * @var SFW2\Core\Database
     */
    protected $database;

    public function __construct(int $pathId, Database $database, Session $session) {
        $this->database = $database;
        $this->session = $session;
    }


    public function index(Request $request, ResponseEngine $responseEngine): Response
    {
        unset($all);
        $content = new Content('SFW2\\Authority\\UserSettings');
        return $content;
    }

    public function read(Request $request, ResponseEngine $responseEngine): Response
    {
        return new Content();
    }

    protected function getUsers() : array {
        $stmt = /** @lang MySQL */
            "SELECT `user`.`FirstName`, `user`.`LastName`, `user`.`Sex`, `user`.`LoginName`, " .
            "IF(`user`.`Birthday` = '0000-00-00', '', `user`.`Birthday`) AS `Birthday`, `user`.`Email`, " .
            "`user`.`Phone1`, `user`.`Phone2`, `user`.`Active` " .
            "FROM `{TABLE_PREFIX}_authority_user` AS `user` ";

        $data = $this->database->selectRow($stmt);
        $data['Birthday'] = $this->database->convertFromMysqlDate($data['Birthday']);
        return $data;
    }










    public function getContent() {
        $this->ctrl->addJSFile('profile');
        $userid = $this->ctrl->getUserId();

        $stmt = /** @lang MySQL */
            "SELECT `sfw_user`.`Id`, " .
            "CONCAT(`sfw_user`.`LastName`, ', ', `sfw_user`.`FirstName`) " .
            "AS `Name`" .
            "FROM `sfw_user` ";

        $users = [];
        $users = [
            array('Id' => '-1', 'Name' => '[Neu anlegen]'),
            ...$this->db->select($stmt)
        ];

        $stmt = /** @lang MySQL */
            "SELECT `sfw_position`.`Id`, `sfw_position`.`Position`, " .
            "`sfw_division`.`Name` AS `Division`, `sfw_position`.`UserId` " .
            "FROM `sfw_position` " .
            "LEFT JOIN `sfw_division` " .
            "ON `sfw_division`.`DivisionId` = `sfw_position`.`DivisionId` " .
            "LEFT JOIN `sfw_user` " .
            "ON `sfw_user`.`id` = `sfw_position`.`UserId` " .
            "WHERE `sfw_position`.`UserId` IN('-1', %s) ";

        $positions = [];
        $positions = [
            [
                'Id'       => '-1',
                'UserId'   => '-1',
                'Position' => '[keine]',
                'Division' => ''
            ],
            ...$this->db->select($stmt, array($userid))
        ];

        $data['Image'] = SFW_Helper::getImageFileName(
            '/public/images/content/thumb/',
            $data['FirstName'],
            $data['LastName']
        );

        $view = new SFW_View();
        $view->assign('data', $data);
        $view->assign('isadmin', $this->ctrl->isAdmin());
        $view->assign('userid', $userid);
        $view->assign('users', $users);
        $view->assign('positions', $positions);
        return
            $this->dto->getErrorProvider()->getContent() .
            $view->getContent('PageContent_Profile');
    }

    private function saveUserData($userid) {

        $tmp = [];

        $tmp['Sex'      ] = $this->dto->getArrayValue('sex', true, 'Die Anrede', array('MALE', 'FEMALE'));
        $tmp['FirstName'] = $this->dto->getName('firstname', true, 'Der Vorname');
        $tmp['LastName' ] = $this->dto->getName('lastname', true, 'Der Nachname');

        $tmp['Birthday' ] = $this->dto->getDate('birthday', false, 'Das Geburtsdatum');

        $tmp['Email'    ] = $this->dto->getEMailAddr('email', false, 'Die E-Mail-Adresse');
        $tmp['Phone1'   ] = $this->dto->getPhoneNb('phone1', true, 'Die Telefonnummer');
        $tmp['Phone2'   ] = $this->dto->getPhoneNb('phone2', false, 'Die 2. Telefonnummer');

        if($this->ctrl->isAdmin()){
            $tmp['Active'   ] = $this->dto->getBool('active');
            $tmp['LoginName'] = $this->dto->getName('loginname', true, 'Der Loginname');
            $tmp['Position' ] = $this->dto->getId('position', true, 'Die Position');

            $add = [];
            $add[] = "`sfw_position`.`id` = %s";
            $add[] = "`sfw_position`.`UserId` IN('-1', %s) ";

            $cnt = $this->db->selectCount(
                'sfw_position',
                $add,
                array($tmp['Position'], $userid)
            );

            if($tmp['Position'] != -1 && $cnt != 1){
                $this->dto->getErrorProvider()->addError(
                    SFW_Error_Provider::IS_WRONG,
                    array('<NAME>' => 'Die Position'),
                    'position'
                );
            }

            if($tmp['Position'] == -1) {
                $stmt = /** @lang MySQL */
                    "UPDATE `sfw_position` " .
                    "SET `UserId` = %s " .
                    "WHERE `UserId` = %s ";

                $params = array('-1', $userid);
            } else {
                $stmt = /** @lang MySQL */
                    "UPDATE `sfw_position` " .
                    "SET `UserId` = %s " .
                    "WHERE `Id` = %s ";

                $params = array($userid, $tmp['Position']);
            }

            if($this->db->update($stmt, $params) > 1) {
                $this->dto->getErrorProvider()->addError(
                    SFW_Error_Provider::INT_ERR
                );
            }

            if($tmp['LoginName'] != '') {
                $add = [];
                $add[] = "`sfw_user`.`id` != %s";
                $add[] = "`sfw_user`.`LoginName` = %s";

                $cnt = $this->db->selectCount(
                    'sfw_user',
                    $add,
                    array($userid, $tmp['LoginName'])
                );

                if($cnt != 0) {
                    $this->dto->getErrorProvider()->addError(
                        SFW_Error_Provider::EXISTS,
                        array('<NAME>' => 'Der Loginname'),
                        'loginname'
                    );
                }
            }
        }

        if($this->dto->getErrorProvider()->hasErrors()) {
            return $tmp;
        }

        if($userid == -1) {
            $stmt = "INSERT INTO `sfw_user` SET ";
        } else {
            $stmt = "UPDATE `sfw_user` SET ";
        }

        $stmt .=
            "`Sex` = %s," .
            "`FirstName` = %s, " .
            "`LastName` = %s, " .
            "`Email` = %s, " .
            "`Phone1` = %s, " .
            "`Phone2` = %s, " .
            "`Birthday` = %s";

        $params = [];
        $params[] = $tmp['Sex'      ];
        $params[] = $tmp['FirstName'];
        $params[] = $tmp['LastName' ];
        $params[] = $tmp['Email'    ];
        $params[] = $tmp['Phone1'   ];
        $params[] = $tmp['Phone2'   ];
        $params[] = $tmp['Birthday' ];


        if($this->ctrl->isAdmin()) {
            $stmt .=
                ", `Active` = %s" .
                ", `LoginName` = %s";
            $params[] = $tmp['Active'   ];
            $params[] = $tmp['LoginName'];
        }

        // TODO: permission, roll und position vereinen!!
        // TODO: Achtung: E-Mail addr wie asdf@t-online.de wird als nicht valide erkannt!!!!
        $params[] = $userid;

        if($userid != -1) {
            $stmt .=  "WHERE `sfw_user`.`id` = %s";
        }

        $this->db->update($stmt, $params);
        $this->dto->setSaveSuccess();
        return $tmp;
    }
}
