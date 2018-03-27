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
 * @property string passwordHash
 * @property string status
 */
class UserEntity extends LeDocBaseEntity
{
    const USER_STATUS_NORMAL = "NORMAL";
    const USER_STATUS_DISABLED = "DISABLED";

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
            "passwordHash",
            "status",
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

    public function saveUser()
    {
        LeDocDataAgent::writeRecordRawContent($this->getEntityDataType(), $this->username, json_encode($this->encodePropertiesForJson()));
    }
}