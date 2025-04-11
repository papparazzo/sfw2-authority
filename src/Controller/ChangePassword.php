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

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SFW2\Authority\Authenticator;
use SFW2\Database\DatabaseException;
use SFW2\Database\DatabaseInterface;
use SFW2\Exception\HttpExceptions\Status4xx\HttpStatus400BadRequest;
use SFW2\Exception\HttpExceptions\Status4xx\HttpStatus403Forbidden;
use SFW2\Render\RenderInterface;

use SFW2\Authority\User;
use SFW2\Authority\Helper\LoginHelperTrait;

use SFW2\Session\SessionInterface;
use SFW2\Validator\Exception;
use SFW2\Validator\Ruleset;
use SFW2\Validator\Validator;
use SFW2\Validator\Validators\ContainsLowerChars;
use SFW2\Validator\Validators\ContainsNumbers;
use SFW2\Validator\Validators\ContainsSpecialChars;
use SFW2\Validator\Validators\ContainsUpperChars;
use SFW2\Validator\Validators\HasMinLength;
use SFW2\Validator\Validators\IsNotEmpty;
use SFW2\Validator\Validators\IsNotSameAs;
use SFW2\Validator\Validators\IsSameAs;

class ChangePassword
{

    use LoginHelperTrait;

    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly SessionInterface $session,
        private readonly RenderInterface $render
    ) {
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $data
     * @return Response
     * @throws DatabaseException
     * @throws Exception
     * @throws HttpStatus400BadRequest
     * @throws HttpStatus403Forbidden
     */
    public function index(Request $request, Response $response, array $data): Response
    {
        $data = $request->getQueryParams();
        if(isset($data['getForm'])) {
            return $this->getForm($request, $response);
        }

        $ruleset = new Ruleset();
        $ruleset->addNewRules(
            'pwd',
            new IsNotEmpty(),
            new HasMinLength(8),
            new IsSameAs($_POST['pwdr']),
            new ContainsNumbers(2),
            new ContainsLowerChars(2),
            new ContainsSpecialChars(2),
            new ContainsUpperChars(2)
        );

        $ruleset->addNewRules('pwdr', new IsNotEmpty(), new IsSameAs($_POST['pwd']));
        if(!isset($_POST['hash'])) {
            $ruleset->addNewRules('pwd', new IsNotSameAs($_POST['oldpwd']));
            $ruleset->addNewRules('oldpwd', new IsNotEmpty());
        }

        $validator = new Validator($ruleset);
        $values = [];

        $success = $validator->validate($_POST, $values);
        if(!$success) {
            $response = $this->render->render($request, $response, ['sfw2_payload' => $values]);
            return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $newPwd = $values['pwd']['value'];

        $auth = new Authenticator($this->database);

        if(isset($_POST['hash'])) {
            $hash = (string)filter_input(INPUT_POST, 'hash');
            $this->validateHash($hash);
            if(!$auth->resetPasswordByHash($hash, $newPwd)) {
                return $this->render->render($request, $response, [
                    'title' => 'Passwort ändern',
                    'description' =>
                        "<p>
                            Das Passwort kann nicht zurückgesetzt werden! Dies kann mehrere Ursachen haben:
                        </p>
                        <ul>
                            <li>Es wurden ungültige Daten übermittelt.</li>
                            <li>Das Passwort wurde bereits zurückgesetzt.</li>
                            <li>Es ist ein interner Fehler aufgetreten.</li>
                            <li>
                                Das Ablaufdatum wurde überschritten. 
                                Bitte achte darauf das Du innerhalb von 
                                <strong>" . $this->getExpireDate(self::$EXPIRE_DATE_OFFSET) . "</strong> reagierst!</li>
                        </ul>
                        <p>
                            Versuche es bitte erneut. Sollte das Problem weiterhin bestehen dann melde dich bitte.
                        </p>",
                    'reload' => true
                ]);
            }
        } else {
            $userId = $this->session->getGlobalEntry(User::class);
            if(is_null($userId)) {
                throw new HttpStatus403Forbidden();
            }
            $user = (new User($this->database))->loadUserById($userId);
            if(!$user->isAuthenticated()) {
                throw new HttpStatus403Forbidden();
            }
            $oldPwd = $values['oldpwd']['value'];
            if(!$auth->resetPasswordByUser($userId, $oldPwd, $newPwd)) {
                return $this->returnError($request, $response);
            }
        }

        return $this->render->render($request, $response, [
            'title' => 'Passwort ändern',
            'description' => 'Dein Passwort wurde erfolgreich geändert',
            'reload' => false
        ]);
    }

    /**
     * @throws HttpStatus400BadRequest
     */
    private function validateHash(string $hash): void
    {
        if(preg_match('/^[0-9a-f.]+$/', $hash) !== 1) {
            throw new HttpStatus400BadRequest();
        }
    }

    /**
     * @throws HttpStatus400BadRequest
     */
    private function getForm(Request $request, Response $response): Response
    {
        if(!isset($request->getQueryParams()['hash'])) {
            return $this->render->render($request, $response, [], 'SFW2\\Authority\\ChangePassword\\ChangePassword');
        }

        $hash = $request->getQueryParams()['hash'];
        $this->validateHash($hash);

        return $this->render->render(
            $request, $response, ['hash' => $hash], 'SFW2\\Authority\\ChangePassword\\ChangePassword'
        );
    }

    protected function returnError(Request $request, Response $response): Response
    {
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
        $response = $this->render->render($request, $response, ['sfw2_payload' => $values]);
        return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
    }
}
