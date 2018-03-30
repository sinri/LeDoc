<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/29
 * Time: 11:09
 */

namespace sinri\ledoc\controller;


use sinri\ark\core\ArkHelper;
use sinri\ledoc\core\LeDocBaseController;
use sinri\ledoc\entity\UserEntity;

class UserController extends LeDocBaseController
{
    public function userList()
    {
        try {
            $needDetails = $this->_readRequest("need_details", 'YES');
            $normalUserOnly = $this->_readRequest("normal_user_only", 'NO');

            $users = [];

            $userNames = UserEntity::getUserNameList();
            foreach ($userNames as $userName) {
                $user = UserEntity::loadUser($userName);
                if ($normalUserOnly === 'YES' && $user->status !== UserEntity::USER_STATUS_NORMAL) {
                    continue;
                }
                $users_item = [
                    "username" => $user->username,
                    "realname" => $user->realname,
                ];
                if ($needDetails === 'YES') {
                    $users_item['folders'] = $user->getUserRelatedFolders();
                    $users_item['status'] = $user->status;
                    $users_item['privileges'] = $user->privileges;
                }
                $users[] = $users_item;
            }

            $this->_sayOK(['users' => $users]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function getUserInfo()
    {
        try {
            $username = $this->_readRequest("username");
            ArkHelper::quickNotEmptyAssert("username should not be empty", $username);
            $user = UserEntity::loadUser($username);
            ArkHelper::quickNotEmptyAssert("user not here", $user);

            $info = [
                "username" => $user->username,
                "realname" => $user->realname,
                "privileges" => $user->privileges,
                "folders" => $user->getUserRelatedFolders(),
                "status" => $user->status,
            ];

            $this->_sayOK(['user' => $info]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function updateUserInfo()
    {
        try {
            $username = $this->_readRequest("username", null, '/^[A-Za-z0-9]+$/');
            ArkHelper::quickNotEmptyAssert("username should not be empty", $username);
            $user = UserEntity::loadUser($username);
            ArkHelper::quickNotEmptyAssert("user not here", $user);

            $data = [];

            $realname = $this->_readRequest("realname");
            if (!empty($realname)) $data['realname'] = $realname;
            $password = $this->_readRequest("password");
            if (!empty($password)) $data['password'] = $password;

            if ($this->user->hasPrivilege(UserEntity::USER_PRIVILEGES_ADMIN)) {
                $status = $this->_readRequest("status");
                if (in_array($status, [UserEntity::USER_STATUS_NORMAL, UserEntity::USER_STATUS_DISABLED])) {
                    $data['status'] = $status;
                }
                $privileges = $this->_readRequest("privileges");
                if (is_array($privileges)) {
                    $data['privileges'] = $privileges;
                }
            }

            if (empty($data)) throw new \Exception("在祂并没有改变，也没有转动的影儿。");

            foreach ($data as $key => $value) {
                if ($key === 'password') {
                    $user->setRealPassword($value);
                } else $user->$key = $value;
            }
            $done = $user->saveUser();
            ArkHelper::quickNotEmptyAssert("cannot change user", $done);
            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function createUser()
    {
        try {
            $username = $this->_readRequest("username", null, '/^[A-Za-z0-9]+$/');
            $password = $this->_readRequest('password');
            $status = $this->_readRequest('status');
            $realname = $this->_readRequest('realname');
            $privileges = $this->_readRequest('privileges');

            ArkHelper::quickNotEmptyAssert("field should not be empty", $username, $password, $realname);
            ArkHelper::quickNotEmptyAssert("status error", in_array($status, [UserEntity::USER_STATUS_NORMAL, UserEntity::USER_STATUS_DISABLED]));
            ArkHelper::quickNotEmptyAssert("privileges should be array", is_array($privileges));
            $user = UserEntity::loadUser($username);
            ArkHelper::quickNotEmptyAssert("user has been here", $user === false);

            $user = new UserEntity();
            $user->username = $username;
            $user->realname = $realname;
            $user->status = $status;
            $user->privileges = $privileges;
            $user->setRealPassword($password);
            $done = $user->saveUser();
            ArkHelper::quickNotEmptyAssert("cannot save user", $done);

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function registerUser()
    {
        try {
            $username = $this->_readRequest("username", null, '/^[A-Za-z0-9]+$/');
            $password = $this->_readRequest('password');
            $status = UserEntity::USER_STATUS_NORMAL;
            $realname = $this->_readRequest('realname');
            $privileges = [];

            ArkHelper::quickNotEmptyAssert("field should not be empty", $username, $password, $realname);
            $user = UserEntity::loadUser($username);
            ArkHelper::quickNotEmptyAssert("user has been here", $user === false);

            $user = new UserEntity();
            $user->username = $username;
            $user->realname = $realname;
            $user->status = $status;
            $user->privileges = $privileges;
            $user->setRealPassword($password);
            $done = $user->saveUser();
            ArkHelper::quickNotEmptyAssert("cannot register user", $done);

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}