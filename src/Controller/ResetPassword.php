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

use SFW2\Authority\Authenticator;
use SFW2\Authority\User;
use SFW2\Core\Interfaces\PathMapInterface;
use SFW2\Core\Mailer\MailerInterface;
use SFW2\Core\Utils\DateTimeHelper;

use SFW2\Database\DatabaseInterface;

use SFW2\Render\RenderInterface;

use SFW2\Validator\Ruleset;
use SFW2\Validator\Validator;
use SFW2\Validator\Validators\IsEMailAddress;
use SFW2\Validator\Validators\IsNotEmpty;


use SFW2\Authority\Helper\LoginHelperTrait;
use Throwable;

class ResetPassword
{
    use LoginHelperTrait;

    private string $loginChangePath;

    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly DateTimeHelper $dateTimeHelper,
        private readonly MailerInterface $mailer,
        private readonly RenderInterface $render,
        PathMapInterface $path,
        int $loginChangePathId
    )
    {
        $this->loginChangePath = $path->getPath($loginChangePathId);
    }

    /**
     * @throws Exception
     */
    public function index(Request $request, Response $response): Response
    {
        if(isset($request->getQueryParams()['getForm'])) {
            return $this->render->render($request, $response, [], 'SFW2\\Authority\\ResetPassword\\ResetPasswordForm');
        }

        $ruleset = new Ruleset();
        $ruleset->addNewRules('user', new IsNotEmpty(), new IsEMailAddress());

        $validator = new Validator($ruleset);
        $values = [];

        $error = !$validator->validate($_POST, $values);

        $response = $this->render->render($request, $response, ['sfw2_payload' => $values]);

        if ($error) {
            return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $user = $values['user']['value'];
        $time = $this->dateTimeHelper->getDateTimeObject(time() + self::$EXPIRE_DATE_OFFSET);

        $auth = new Authenticator($this->database);
        $hash = $auth->getHash($user, $time);

        if(is_null($hash)) {
            return $this->returnError($request, $response);
        }

        try {
            $user = (new User($this->database))->loadUserByEmailAddress($user);
        } catch(Throwable) {
            return $this->returnError($request, $response);
        }

        $expireDate = $this->getExpireDate(self::$EXPIRE_DATE_OFFSET);

        $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === 'on') ? 'https://' : 'http://';
        $domain = filter_var($_SERVER['HTTP_HOST'], FILTER_VALIDATE_DOMAIN);
        $link =
            $protocol . $domain .
            '?getForm=' . urldecode($this->loginChangePath) . '&hash=' . urlencode($hash) . '#page-content-start';

        $data = [
            'name' => $user->getFullName(),
            'hash' => $hash,
            'link' => $link,
            'expire' => $expireDate
        ];

        $this->mailer->send(
            $user->getMailAddr(),
            'Neues Passwort',
            'SFW2\\Authority\\ResetPassword\\ConfirmPasswordResetEmail',
            $data
        );

        return $this->render->render($request, $response, [
            'title' => 'Passwort rücksetzen',
            'description' => "
                <p>
                    Bestätigungsnachricht wurde erfolgreich verschickt.
                </p>
                <p>
                    Bitte klicke auf den Bestätigungslink den du per E-Mail erhälst 
                    um dein neues Passwort eingeben zu können.
                </p>
                <p>
                    Der Bestätigungslink ist <strong>$expireDate</strong>
                    gültig. Solltest du nicht innerhalb dieser Zeit auf den Link geklickt
                    haben so wird dieser ungültig und du musst abermals ein neuen Bestätigunslink anfordern!
                </p>
            ",
            'reload' => false
        ]);
    }

    protected function returnError(Request $request, Response $response): Response
    {
        $values = [
            'user' => [
                'hint' => 'Ungültiger Benutzername übergeben!',
                'value' => ''
            ]
        ];
        $response = $this->render->render($request, $response, ['sfw2_payload' => $values]);
        return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
    }
}
