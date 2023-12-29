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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SFW2\Core\Utils\DateTimeHelper;
use SFW2\Database\DatabaseInterface;
use SFW2\Routing\AbstractController;
use SFW2\Routing\PathMap\PathMapInterface;
use SFW2\Routing\ResponseEngine;

use SFW2\Validator\Ruleset;
use SFW2\Validator\Validator;
use SFW2\Validator\Validators\IsNotEmpty;


use SFW2\Authority\Helper\LoginHelperTrait;

class ResetPassword extends AbstractController
{
    use LoginHelperTrait;

    /**
     * @var string
     */
    protected $loginChangePath = '';

    public function __construct(
        private readonly DatabaseInterface $database, private readonly DateTimeHelper $dateTimeHelper,
        PathMapInterface $path, $loginChangePathId = null
    )
    {
        if($loginChangePathId != null) {
            $this->loginChangePath = $path->getPath($loginChangePathId);
        }
    }

    public function index(Request $request, ResponseEngine $responseEngine): Response
    {
        $rulset = new Ruleset();
        $rulset->addNewRules('user', new IsNotEmpty());

        $validator = new Validator($rulset);
        $values = [];

        $error = !$validator->validate($_POST, $values);

        $response = $responseEngine->render($request, ['sfw2_payload' => $values]);

        if ($error) {
            return $response->withStatus(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $user = $values['user']['value'];
        $hash = $this->getHash($user);

        if(is_null($hash)) {
            // No hints on non exsiting user...
            return $responseEngine->render($request);
        }

        $stmt = /** @lang MySQL */
            "SELECT CONCAT(`FirstName`, ' ', `LastName`) AS `Name` " .
            "FROM `{TABLE_PREFIX}_authority_user` " .
            "WHERE `LoginName` = %s";

        $uname = $this->database->selectSingle($stmt, [$user]);

        $data = [
            'name' => $uname,
            'hash' => $hash,
            'path' => 'https://' . filter_var($_SERVER['HTTP_HOST'], FILTER_VALIDATE_DOMAIN) . $this->loginChangePath . "?do=confirm&hash=$hash",
            'expire' => $this->getExpireDate(self::$EXPIRE_DATE_OFFSET)
        ];

        // TODO send mail

        return $responseEngine->render($request);
    }

    protected function getHash(string $user): ?string
    {
        $hash = uniqid(more_entropy: true);

        $stmt = /** @lang MySQL */
            "UPDATE `{TABLE_PREFIX}_authority_user` " .
            "SET `ResetExpireDate` = %s, `ResetHash` = %s " .
            "WHERE `LoginName` = %s ";


        $time = $this->dateTimeHelper->getDateTimeObject(time() + self::$EXPIRE_DATE_OFFSET);
        $val = $this->database->update($stmt, [$time, $hash, $user]);

        if($val !== 1) {
            return null;
        }

        return $hash;
    }
}
