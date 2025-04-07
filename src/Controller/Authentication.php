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

use Fig\Http\Message\StatusCodeInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SFW2\Authority\Authenticator;
use SFW2\Database\DatabaseException;
use SFW2\Database\DatabaseInterface;
use SFW2\Authority\User;
use SFW2\Render\RenderInterface;
use SFW2\Session\SessionInterface;
use SFW2\Validator\Exception;

final class Authentication
{
    public function __construct(
        protected SessionInterface  $session,
        protected DatabaseInterface $database,
        private readonly RenderInterface  $render,
        protected ?string           $loginResetPath = null
    ) {
    }

    /**
     * @throws Exception
     * @throws DatabaseException
     */
    public function getLogin(Request $request, Response $response, array $data): Response
    {
        $userId = $this->session->getGlobalEntry(User::class);

        $user = (new User($this->database))->loadUserById($userId);

        if (!$user->isAuthenticated()) {
            return $this->render->render($request, $response, [], 'SFW2\\Authority\\Authentication\\LoginForm');
        }

        $data = [
            'user_name' => $user->getFullName()
        ];
        return $this->render->render($request, $response, $data, 'SFW2\\Authority\\Authentication\\LogoutForm');
    }

    /**
     * @throws DatabaseException
     */
    public function postLogin(Request $request, Response $response, array $data): Response
    {
        $auth = new Authenticator($this->database);
        $user = $auth->authenticateUser(
            (string)filter_input(INPUT_POST, 'usr'),
            (string)filter_input(INPUT_POST, 'pwd')
        );

        if (!$user->isAuthenticated()) {
            $values['pwd']['hint'] = 'Es wurden ungültige Daten übermittelt!';
            $values['usr']['hint'] = ' ';
            $response = $this->render->render($request, $response, ['sfw2_payload' => $values]);
            return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $this->session->setGlobalEntry(User::class, $user->getUserId());
        $this->session->regenerateSession();

        $data = [];
        $data['user_name'] = $user->getFirstName();
        $data['user_id'] = $user->getUserId();
        $data['authenticated'] = $user->isAuthenticated();

        $request = $request->withAttribute('sfw2_authority', $data);
        return
            $this->render->render(
                $request,
                $response,
                [
                    'title' => 'Anmelden',
                    'description' => "
                        Hallo <strong>{$user->getFirstName()}</strong>,<br />
                        du wurdest erfolgreich angemeldet. 
                        Zum Abmelden klicke bitte oben rechts auf <strong>abmelden</strong>
                    ",
                    'reload' => true
                ]
            );
    }

    public function postLogout(Request $request, Response $response, array $data): Response
    {
        $this->session->delGlobalEntry(User::class);
        $this->session->regenerateSession();
        return
            $this->render->render(
                $request,
                $response,
                [
                    'title' => 'Abmelden',
                    'description' =>
                        'Du wurdest erfolgreich abgemeldet. ' .
                        'Um dich erneut anzumelden klicke bitte oben rechts auf Login.',
                    'reload' => true
                ]
            );
    }
}
