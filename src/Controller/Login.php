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
use SFW2\Authority\User;

use SFW2\Core\Session;

class Login extends AbstractController {

    /**
     * @var \SFW2\Routing\User
     */
    protected $user;

    /**
     * @var \SFW2\Core\Session
     */
    protected $session;

    /**
     * @var string
     */
    protected $loginResetPath = '';

    public function __construct(int $pathId, PathMap $path, User $user, Session $session, $loginResetPathId = null) {
        parent::__construct($pathId);
        $this->user = $user;
        $this->session = $session;

        if($loginResetPathId != null) {
            $this->loginResetPath = $path->getPath($loginResetPathId);
        }
    }

    public function index($all = false) {
        unset($all);
        $error = !$this->user->authenticateUser(
            (string)filter_input(INPUT_POST, 'usr'),
            (string)filter_input(INPUT_POST, 'pwd')
        );
        $this->session->setGlobalEntry(User::class, $this->user->getUserId());
        $this->session->regenerateSession();

        $content = new Content('', $error);
        $content->assign('user', $this->user->getFirstName());
        return $content;
    }

    public function logoff() {
        $this->user->reset();
        $this->session->setGlobalEntry(User::class, $this->user->getUserId());
        return new Content();
    }
}
