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

namespace SFW2\Authority\Controller;

use SFW2\Routing\AbstractController;
use SFW2\Routing\Result\Content;
use SFW2\Routing\PathMap\PathMap;

use SFW2\Core\DataValidator;
use SFW2\Core\Database;
use SFW2\Core\Helper;
use SFW2\Core\Session;

use SFW2\Authority\User;

use SFW2\Authority\Helper\LoginHelperTrait;

class LoginResetPassword extends AbstractController {

    use LoginHelperTrait;

    /**
     * @var \SFW2\Routing\User
     */
    protected $user;

    /**
     * @var SFW2\Core\Database
     */
    protected $database;

    /**
     * @var \SFW2\Core\Session
     */
    protected $session;

    /**
     * @var string
     */
    protected $loginChangePath = '';

    public function __construct(int $pathId, PathMap $path, Database $database, User $user, Session $session, $loginChangePathId = null) {
        parent::__construct($pathId);
        $this->database = $database;
        $this->user = $user;
        $this->session = $session;
        if($loginChangePathId != null) {
            $this->loginChangePath = $path->getPath($loginChangePathId);
        }
    }

    public function index($all = false) : Content {
        unset($all);
        $content = new Content('SFW2\\Authority\\LoginReset\\LoginReset');
        $content->assign('lastPage', $this->session->getGlobalEntry('current_path', ''));
        return $content;
    }

    public function request() : Content {
        $content = new Content('SFW2\\Authority\\LoginReset\\SendSuccess');

        $rulset = [
            'user' => ['isNotEmpty'],
            'addr' => ['isNotEmpty', 'isEMailAddress'],
        ];

        $values = [];

        $validator = new DataValidator($rulset);
        $error = $validator->validate($_POST, $values);

        if(!$error) {
            $content->setError(true);
            $content->assignArray($values);
            return $content;
        }

        $user = $values['user']['value'];
        $addr = $values['addr']['value'];
        $hash = $this->getHash($user, $addr);

        if($hash == '') {
            $values['addr']['hint'] = 'Es wurden ungültige Daten übermittelt!';
            $values['user']['hint'] = ' ';
            $content->setError(true);
            $content->assignArray($values);
            return $content;
        }

        $stmt =
            "SELECT CONCAT(`FirstName`, ' ', `LastName`) AS `Name` " .
            "FROM `{TABLE_PREFIX}_user` " .
            "WHERE `Email` = '%s' AND `LoginName` = '%s'";

        $uname = $this->database->selectSingle($stmt, [$addr, $user]);
/*
        $mail = new SFW_Mailer();
        $mail->confirmPasswordReset($addr, $uname, $hash);
        $this->loginChangePath?do=confirm&hash=$hash;
 */
        $content->assign('expire', $this->getExpireDate($this->getExpireDateOffset()));
        $content->assign('name', $uname . ' (' . $addr . ')');
        $content->assign('lastPage', $this->session->getGlobalEntry('current_path', ''));
        return $content;
    }

    protected function getHash(string $user, string $addr) : string {
        $hash = md5($user . $addr . time() . Helper::getRandomInt());

        $stmt =
            "UPDATE `{TABLE_PREFIX}_user` " .
            "SET `ResetExpireDate` = '%s', `ResetHash` = '%s' " .
            "WHERE `Email` = '%s' AND `LoginName` = '%s' ";

        $val = $this->database->update($stmt, [$this->getMySQLExpireDate(), $hash, $addr, $user]);

        if($val !== 1) {
            return '';
        }

        return $hash;
    }
}
