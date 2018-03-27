<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/27
 * Time: 14:48
 */

namespace sinri\ledoc\entity;

/**
 *
 */
use sinri\ledoc\core\LeDocBaseEntity;
use sinri\ledoc\core\LeDocDataAgent;

/**
 * Class SessionEntity
 * @package sinri\ledoc\entity
 * @property string $token
 * @property string username
 * @property int expire
 */
class SessionEntity extends LeDocBaseEntity
{
    const DATA_TYPE_SESSION = "session";

    /**
     * @return string
     */
    public function getEntityDataType()
    {
        return self::DATA_TYPE_SESSION;
    }

    /**
     * @return string[]
     */
    public function propertyList()
    {
        return ["token", "username", "expire"];
    }

    /**
     * @param string $token
     * @return bool|SessionEntity
     */
    public static function verifyToken($token)
    {
        $raw = LeDocDataAgent::readRecordRawContent(self::DATA_TYPE_SESSION, $token);
        if ($raw === false) return false;

        $sessionEntity = new SessionEntity();
        $sessionEntity->loadPropertiesFromJson(json_decode($raw, true));
        if ($sessionEntity->expire <= time()) {
            //expired
            return false;
        }

        return $sessionEntity;
    }

    /**
     * @param string $username
     * @param string $token such as uniqid(date('YmdHis_'));
     * @param int $expire such as time()+3600;
     * @return bool|int
     */
    public static function createSession($username, $token, $expire)
    {
        $sessionEntity = new SessionEntity();
        $sessionEntity->token = $token;
        $sessionEntity->username = $username;
        $sessionEntity->expire = $expire;
        return LeDocDataAgent::writeRecordRawContent($sessionEntity->getEntityDataType(), $sessionEntity->token, json_encode($sessionEntity->encodePropertiesForJson()));
    }

    public function suicide()
    {
        return LeDocDataAgent::removeRecord($this->getEntityDataType(), $this->token);
    }

    public static function cleanAllOldSessions()
    {
        $tokens = LeDocDataAgent::getRecordOriginalNamesWithGlob(self::DATA_TYPE_SESSION, '*');
        foreach ($tokens as $token) {
            if (!SessionEntity::verifyToken($token)) {
                LeDocDataAgent::removeRecord(self::DATA_TYPE_SESSION, $token);
            }
        }
    }
}