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

            ArkHelper::quickNotEmptyAssert("登录参数不可为空", $username, $password);

            $user = UserEntity::loadUser($username);
            ArkHelper::quickNotEmptyAssert("用户不存在", $user);
            ArkHelper::quickNotEmptyAssert("密码错误", (password_verify($password, $user->passwordHash)));
            ArkHelper::quickNotEmptyAssert("用户已经不可用", ($user->status === UserEntity::USER_STATUS_NORMAL));

            $token = uniqid(date('YmdHis_'));
            $expire = time() + LeDoc::configOfSessionLifetime();
            $done = SessionEntity::createSession($user->username, $token, $expire);
            ArkHelper::quickNotEmptyAssert("无法创建会话", $done);

            $this->_sayOK([
                "username" => $user->username,
                "realname" => $user->realname,
                "token" => $token,
                "expire" => $expire
            ]);
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

    public function currentUserInfo()
    {
        try {
            $this->_sayOK([
                "username" => $this->user->username,
                "realname" => $this->user->realname,
                "privileges" => $this->user->privileges,
                "folders" => $this->user->getUserRelatedFolders(),
                "status" => $this->user->status,
            ]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}