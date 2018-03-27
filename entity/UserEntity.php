<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/27
 * Time: 10:36
 */

namespace sinri\ledoc\entity;


use sinri\ledoc\core\LeDocBaseEntity;
use sinri\ledoc\core\LeDocDataAgent;

/**
 * @property string username
 * @property string realname
 * @property string passwordHash
 * @property string status
 * @property string privileges
 */
class UserEntity extends LeDocBaseEntity
{
    const USER_STATUS_NORMAL = "NORMAL";
    const USER_STATUS_DISABLED = "DISABLED";

    const USER_PRIVILEGES_ADMIN = "PRIVILEGE_ADMIN";

    /**
     * @return string
     */
    public function getEntityDataType()
    {
        return "user";
    }

    /**
     * @return string[]
     */
    public function propertyList()
    {
        return [
            "username",
            "realname",
            "passwordHash",
            "status",
            "privileges",
        ];
    }

    /**
     * @param string $username
     * @return bool|UserEntity
     */
    public static function loadUser($username)
    {
        $userEntity = new UserEntity();
        $raw = LeDocDataAgent::readRecordRawContent($userEntity->getEntityDataType(), $username);
        if ($raw === false) return false;
        $userEntity->loadPropertiesFromJson(json_decode($raw, true));
        return $userEntity;
    }

    public function setRealPassword(string $realPassword)
    {
        $this->passwordHash = password_hash($realPassword, PASSWORD_DEFAULT);
    }

    public function saveUser()
    {
        LeDocDataAgent::writeRecordRawContent($this->getEntityDataType(), $this->username, json_encode($this->encodePropertiesForJson()));
    }
}