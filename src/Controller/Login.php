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

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SFW2\Routing\AbstractController;
use SFW2\Authority\User;
use SFW2\Routing\ResponseEngine;
use SFW2\Session\SessionInterface;

class Login extends AbstractController {

    protected User $user;

    protected string $loginResetPath = '';

    public function __construct(
        protected SessionInterface $session
       # PathMapInterface $path,
       # User $user,
       # SessionInterface $session,
       # $loginResetPathId = null
    ) {
      #  $this->user = $user;

     #   if($loginResetPathId != null) {
     #       $this->loginResetPath = $path->getPath($loginResetPathId);
     #   }
    }

    public function index(Request $request, ResponseEngine $responseEngine): Response
    {

 #       $error = !$this->user->authenticateUser(
 #           (string)filter_input(INPUT_POST, 'usr'),
 #           (string)filter_input(INPUT_POST, 'pwd')
 #       );
 #       $this->session->setGlobalEntry(User::class, $this->user->getUserId());
        $this->session->regenerateSession();

        #$content = new Content('', $error);
        #$content->assign('user', $this->user->getFirstName());

        #$data['user_name'] = $this->user->getFirstName();

#        $request = $request->withAttribute('sfw2_authority', $data);
        return $responseEngine->render($request);
    }

    public function logoff(Request $request, ResponseEngine $responseEngine): Response
    {
        $this->user->reset();
        $this->session->setGlobalEntry(User::class, $this->user->getUserId());
        return $responseEngine->render($request);
    }
}
