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

use SFW2\Routing\AbstractController;
use SFW2\Routing\Result\Content;
use SFW2\Routing\PathMap\PathMap;

use SFW2\Authority\User;
use SFW2\Authority\Helper\LoginHelperTrait;

use SFW2\Validator\Ruleset;
use SFW2\Validator\Validator;
use SFW2\Validator\Validators\IsNotEmpty;
use SFW2\Validator\Validators\IsSameAs;

use SFW2\Core\Session;

class LoginChangePassword extends AbstractController {

    use LoginHelperTrait;

    /**
     * @var \SFW2\Routing\User
     */
    protected $user;

    /**
     * @var \SFW2\Core\Session
     */
    protected $session;

    protected $loginResetPath = '';

    public function __construct(int $pathId, PathMap $path, User $user, Session $session, $loginResetPathId = null) {
        parent::__construct($pathId);
        $this->user = $user;
        $this->session = $session;

        if($loginResetPathId != null) {
        }
    }

    public function index($all = false) : Content {
        unset($all);

        if(!$this->user->isAuthenticated()) {
            $content = new Content('SFW2\\Authority\\LoginChangePassword\\ChangeError');
            return $content;
        }

        $content = new Content('SFW2\\Authority\\LoginChangePassword\\ChangePassword');
        $content->assign('hash', '');
        return $content;
    }

    public function confirm() : Content {
        $hash = (string)filter_input(INPUT_GET, 'hash');
        $error = !$this->user->authenticateUserByHash($hash);

        $this->session->setGlobalEntry(User::class, $this->user->getUserId());
        $this->session->regenerateSession();

        if($error) {
            $content = new Content('SFW2\\Authority\\LoginChangePassword\\ResetError');
            $content->assign('expire', $this->getExpireDate($this->getExpireDateOffset()));
            return $content;
        }

        $this->session->setPathEntry('hash', $hash);
        $content = new Content('SFW2\\Authority\\LoginChangePassword\\ChangePassword');
        $content->assign('hash', $hash);
        return $content;
    }

    public function changePassword() : Content {
        $content = new Content();

        $hash = filter_input(INPUT_POST, 'hash');

        if($hash == '' && !$this->user->isAuthenticated()) {
            return $this->returnError();
        }

        if($hash != '' && $this->session->getPathEntry('hash', '') != $hash) {
            return $this->returnError();
        }

        $rulset = new Ruleset();
        $rulset->addNewRules('pwd', new IsNotEmpty(), new IsSameAs($_POST['pwdr']));
        $rulset->addNewRules('pwdr', new IsNotEmpty(), new IsSameAs($_POST['pwd']));

        if($hash == '') {
            $rulset->addNewRules('oldpwd', new IsNotEmpty());
        }

        $validator = new Validator($rulset);
        $values = [];

        $error = !$validator->validate($_POST, $values);

        if($error) {
            $content->setError(true);
            $content->assignArray($values);
            return $content;
        }

        $newPwd = $values['pwd']['value'];
        if($hash == '') {
            $oldPwd = $values['oldpwd']['value'];
            $error = !$this->user->resetPassword($oldPwd, $newPwd);
        } else {
            $this->session->delPathEntry('hash');
            $error = !$this->user->resetPasswordByHash($newPwd);
        }

        if($error) {
            return $this->returnError();
        }

        return $content;
    }

    protected function returnError() : Content {
        $content = new Content();
        $values = [
            'oldpwd' => [
                'hint' => ' ',
                'value' => ''
            ],
            'pwd' => [
                'hint' => ' ',
                'value' => ''
            ],
            'pwdr' => [
                'hint' => 'Das Ändern des Passwortes schlug fehl!',
                'value' => ''
            ]
        ];
        $content->assignArray($values);
        $content->setError(true);
        return $content;
    }
}
