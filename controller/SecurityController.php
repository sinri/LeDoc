<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/26
 * Time: 23:39
 */

namespace sinri\ledoc\controller;


use sinri\ark\core\ArkHelper;
use sinri\ledoc\core\LeDoc;
use sinri\ledoc\core\LeDocBaseController;
use sinri\ledoc\entity\SessionEntity;
use sinri\ledoc\entity\UserEntity;

class SecurityController extends LeDocBaseController
{
    public function login()
    {
        try {
            // act login
            $username = $this->_readRequest("username", "");
            $password = $this->_readRequest("password", "");

            ArkHelper::quickNotEmptyAssert("Fields should not be empty", $username, $password);

            $user = UserEntity::loadUser($username);
            ArkHelper::quickNotEmptyAssert("User does not exist", $user);
            ArkHelper::quickNotEmptyAssert("Password Error", (!password_verify($password, $user->passwordHash)));
            ArkHelper::quickNotEmptyAssert("User is no longer active", ($user->status === UserEntity::USER_STATUS_NORMAL));

            $token = uniqid(date('YmdHis_'));
            $expire = time() + LeDoc::configOfSessionLifetime();
            $done = SessionEntity::createSession($user->username, $token, $expire);
            ArkHelper::quickNotEmptyAssert("Cannot create session", $done);

            $this->_sayOK(["token" => $token, "expire" => $expire]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function logout()
    {
        try {
            $done = $this->session->suicide();
            $this->_sayOK(['done' => $done]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}