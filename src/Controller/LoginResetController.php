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
use SFW2\Core\Database;
use SFW2\Core\Helper;
use SFW2\Core\Session;

class LoginResetController extends AbstractController {

    const EXPIRE_DATE_OFFSET = 86400; #24 * 60 * 60;

    const STATE_START = 'start';
    const STATE_SEND  = 'send';
    const STATE_OK    = 'ok';
    const STATE_ERROR = 'error';

    /**
     * @var SFW2\Core\Database
     */
    protected $database;

    /**
     * @var \SFW2\Core\Session
     */
    protected $session;

    public function __construct(int $pathId, Database $database, Session $session) {
        parent::__construct($pathId);
        $this->database = $database;
        $this->session = $session;
    }

    public function index($all = false) {
        $content = new Content('SFW2\\Authority\\LoginReset\\LoginReset');
        $content->assign('expire', $this->getExpireDate(self::EXPIRE_DATE_OFFSET));
        $content->assign('lastPage', $this->session->getGlobalEntry('current_path', ''));



        $content->assign('name', 'Hans');
        $content->assign('expire', '3 Wochen');


        return $content;















        $content = new Content('content/login/login');
        $content->assign('loginResetPath', $this->loginResetPath);
        $content->assign('isAllreadyLoggedIn', $this->user->isAuthenticated());
        $content->assign('firstname', $this->user->getFirstName());

        return $content;
    }










    public function reset() {
        $tmp = [];

        $tmp['name'] = 't';#$this->dto->getName(
#            'name',
#            true,
#            'Der Loginname'
#        );
        $tmp['addr'] = 'u';#$this->dto->getEMailAddr(
#            'addr',
#            true,
#            'Die E-Mail-Adresse'
#        );
        if(
           # !$this->dto->getErrorProvider()->hasErrors() &&
            $this->operate($tmp['addr'], $tmp['name'])
        ) {
            return $this->showResetScreen(self::STATE_START, true);
        }

        return $this->showResetScreen(self::STATE_SEND);
    }

    protected function showResetScreen($state, $showError = false) {
    }

    public function confirm() {
        $state = 'error';
        if($this->validateHash()) {
            $state = 'ok';
        }
        $view = new \SFW\View();
        $view->assign('state', $state);
        $view->assign('expire', $this->getExpireDate(
            self::EXPIRE_DATE_OFFSET)
        );

        return $view->getContent(
            $this->config->getTemplateFile('LoginReset')
        );
    }

    protected function operate($addr, &$name) {
        $hash = md5($name . $addr . time() . Helper::getRandomInt());

        $stmt =
            "UPDATE `sfw2_user` " .
            "SET `ResetExpireDate` = '%s', " .
            "`ResetHash` = '%s' " .
            "WHERE `Email` = '%s' AND `LoginName` = '%s' ";

        $val = $this->database->update(
            $stmt,
            [$this->getMySQLExpireDate(), $hash, $addr, $name]
        );

        if($val !== 1) {
#            $this->dto->getErrorProvider()->addError(
#                SFW_Error_Provider::IS_WRONG,
#                array('<NAME>' => 'der Loginname bzw. Die E-Mail-Adresse'),
#                array('name', 'addr')
#            );
            return false;
        }

        $stmt =
            "SELECT CONCAT(`FirstName`, ' ', `LastName`) AS `Name` " .
            "FROM `sfw2_user` " .
            "WHERE `Email` = '%s' AND `LoginName` = '%s'";

        $uname = $this->database->selectSingle(
            $stmt,
            array($addr, $name)
        );
/*
        $mail = new SFW_Mailer();
        if(!$mail->confirmPasswordReset($addr, $uname, $hash)) {
            $this->dto->getErrorProvider()->addError(
                SFW_Error_Provider::SEND_FAILED
            );
            return false;
        }
 *
 */
        $name = $uname;
        return true;
    }

    /**
     *
     * @return boolean

            $stmt =
            "UPDATE `sfw2_user` " .
            "SET `ResetExpireDate` = NULL, " .
            "`ResetHash` = '', " .
            "`Password` = MD5('%s') " .
            "WHERE `Id` = '%s'";

        if($this->db->update($stmt, array($pwd, $row['Id'])) !== 1) {
            return false;
        }
        return true;
     */

    protected function validateHash() {

        $hash = '';
        try {
            $hash = $this->dto->getHash('hash');
        } catch(Exception $e) {
            // -#- NOOP
        }

        $stmt =
            "SELECT `Id`, `LoginName`, `EMail`, " .
            "CONCAT(`FirstName`, ' ', `LastName`) AS `Name` " .
            "FROM `sfw2_user` " .
            "WHERE `ResetExpireDate` >= NOW() " .
            "AND `ResetHash` = '%s'";

        $row = $this->database->selectRow($stmt, [$hash]);

        if(empty($row)){
            return false;
        }
        return true;
    }

    protected function getExpireDate($date) {
        return intval($date / 60 / 60) . ' Stunden';
    }

    protected function getMySQLExpireDate() {
        $time = time() + self::EXPIRE_DATE_OFFSET;
        return date('Y-m-d H:i:s', $time);
    }
}
