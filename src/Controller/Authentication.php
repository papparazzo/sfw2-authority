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

use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SFW2\Core\HttpExceptions\HttpForbidden;
use SFW2\Database\DatabaseInterface;
use SFW2\Routing\AbstractController;
use SFW2\Authority\User;
use SFW2\Routing\ResponseEngine;
use SFW2\Session\SessionInterface;

final class Authentication extends AbstractController
{
    protected User $user;

    /**
     * @throws HttpForbidden
     */
    public function __construct(
        protected SessionInterface  $session,
        protected DatabaseInterface $database,
        protected ?string           $loginResetPath = null
    )
    {
        $this->user = new User($this->database);
    }

    /**
     * @throws Exception
     */
    public function index(Request $request, ResponseEngine $responseEngine): Response
    {
        $error = !$this->user->authenticateUser(
            (string)filter_input(INPUT_POST, 'usr'),
            (string)filter_input(INPUT_POST, 'pwd')
        );

        if ($error) {
            $values['pwd']['hint'] = 'Es wurden ungültige Daten übermittelt!';
            $values['usr']['hint'] = ' ';
            $response = $responseEngine->render($request, ['sfw2_payload' => $values]);
            return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $this->session->setGlobalEntry(User::class, $this->user->getUserId());
        $this->session->regenerateSession();

        $data = [];
        $data['user_name'] = $this->user->getFirstName();
        $data['user_id'] = $this->user->getUserId();
        $data['authenticated'] = $this->user->isAuthenticated();

        $request = $request->withAttribute('sfw2_authority', $data);
        return $responseEngine->render($request, [
            'title' => 'Anmelden',
            'description' =>
                "Hallo <strong>{$this->user->getFirstName()}</strong>,<br />
                du wurdest erfolgreich angemeldet. Zum Abmelden klicke bitte oben rechts auf <strong>abmelden</strong>",
            'reload' => true
        ]);
    }

    public function logout(Request $request, ResponseEngine $responseEngine): Response
    {
        $this->user->reset();
        $this->session->delGlobalEntry(User::class);
        $this->session->regenerateSession();
        return $responseEngine->render($request, [
            'title' => 'Abmelden',
            'description' =>
                'Du wurdest erfolgreich abgemeldet. Um dich erneut anzumelden klicke bitte oben rechts auf Login.',
            'reload' => true
        ]);
    }
}
