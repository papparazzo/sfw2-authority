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

        if($this->user->isAuthenticated()) {

        }




        $content = new Content('SFW2\\Authority\\LoginChangePassword\\ChangePassword');
        $content->assign('lastPage', $this->session->getGlobalEntry('current_path', ''));
        return $content;
    }


    public function changePassword() : Content {
#        $content = new Content('SFW2\\Authority\\LoginChangePassword\\InsertPassword');
#        $content->assign('lastPage', $this->session->getGlobalEntry('current_path', ''));
#        return $content;
    }


    public function confirm() : Content {
        $error = !$this->user->authenticateUserByHash((string)filter_input(INPUT_GET, 'hash'));

        $this->session->setGlobalEntry(User::class, $this->user->getUserId());
        $this->session->regenerateSession();

        if($error) {
            $content = new Content('SFW2\\Authority\\LoginChangePassword\\ResetError');
            $content->assign('expire', $this->getExpireDate($this->getExpireDateOffset()));
            $content->assign('lastPage', $this->session->getGlobalEntry('current_path', ''));
            return $content;
        }

        #$content->assign('user', $this->user->getFirstName());

        #return $this->changePassword();
    }
}