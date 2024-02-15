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
use SFW2\Core\HttpExceptions\HttpForbidden;
use SFW2\Database\DatabaseInterface;
use SFW2\Routing\AbstractController;

use SFW2\Authority\User;
use SFW2\Authority\Helper\LoginHelperTrait;

use SFW2\Routing\ResponseEngine;
use SFW2\Session\SessionInterface;
use SFW2\Validator\Ruleset;
use SFW2\Validator\Validator;
use SFW2\Validator\Validators\IsNotEmpty;
use SFW2\Validator\Validators\IsSameAs;

class ChangePassword extends AbstractController {

    use LoginHelperTrait;

    protected User $user;

    /**
     * @throws HttpForbidden
     */
    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly SessionInterface $session
    )
    {
        $userId = $this->session->getGlobalEntry(User::class);
        $this->user = new User($this->database, $userId);
    }

    /**
     * @param Request $request
     * @param ResponseEngine $responseEngine
     * @return Response
     * @throws HttpBadRequest
     * @throws Exception
     * @throws HttpForbidden
     */
    public function index(Request $request, ResponseEngine $responseEngine): Response
    {
        if(!$this->user->isAuthenticated()) {
            throw new HttpForbidden();
        }

        $rulset = new Ruleset();
        $rulset->addNewRules('pwd', new IsNotEmpty(), new IsSameAs($_POST['pwdr']));
        $rulset->addNewRules('pwdr', new IsNotEmpty(), new IsSameAs($_POST['pwd']));
        $rulset->addNewRules('oldpwd', new IsNotEmpty());

        $validator = new Validator($rulset);
        $values = [];

        $error = !$validator->validate($_POST, $values);

        if($error) {
            $response = $responseEngine->render($request, ['sfw2_payload' => $values]);
            return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $newPwd = $values['pwd']['value'];
        $oldPwd = $values['oldpwd']['value'];
        $error = !$this->user->resetPassword($oldPwd, $newPwd);

        if($error) {
            return $this->returnError($request, $responseEngine);
        }

        return $responseEngine->render($request);
    }

    public function confirm(Request $request, ResponseEngine $responseEngine): Response
    {
        $hash = (string)filter_input(INPUT_GET, 'hash');
        $error = !$this->user->authenticateUserByHash($hash);

        $this->session->setGlobalEntry(User::class, $this->user->getUserId());
        $this->session->regenerateSession();

        if($error) {
            return $responseEngine->render(
                request: $request,
                data: ['expire' => $this->getExpireDate(self::$EXPIRE_DATE_OFFSET)],
                template: "SFW2\\Authority\\ChangePassword\\ResetError"
            );
        }

        $this->session->setPathEntry('hash', $hash);

        return $responseEngine->render(
            request: $request,
            data: ['hash' => $hash],
            template: "SFW2\\Authority\\ChangePassword\\ChangePassword"
        );
    }

    /**
     * @throws HttpForbidden
     */
    public function changePassword(Request $request, ResponseEngine $responseEngine): Response
    {
        $hash = filter_input(INPUT_POST, 'hash');

        if($hash != '' && $this->session->getPathEntry('hash', '') != $hash) {
            throw new HttpForbidden();
        }

        $rulset = new Ruleset();
        $rulset->addNewRules('pwd', new IsNotEmpty(), new IsSameAs($_POST['pwdr']));
        $rulset->addNewRules('pwdr', new IsNotEmpty(), new IsSameAs($_POST['pwd']));

        $validator = new Validator($rulset);
        $values = [];

        $error = !$validator->validate($_POST, $values);

        if($error) {
            $response = $responseEngine->render($request, ['sfw2_payload' => $values]);
            return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $newPwd = $values['pwd']['value'];
        $this->session->delPathEntry('hash');
        $error = !$this->user->resetPasswordByHash($newPwd);

        if($error) {
            return $this->returnError($request, $responseEngine);
        }

        return $responseEngine->render($request);
    }

    protected function returnError(Request $request, ResponseEngine $responseEngine): Response
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
        $response = $responseEngine->render($request, ['sfw2_payload' => $values]);
        return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
    }
}
