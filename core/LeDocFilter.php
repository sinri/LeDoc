<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/26
 * Time: 23:33
 */

namespace sinri\ledoc\core;


use sinri\ark\web\ArkRequestFilter;
use sinri\ledoc\entity\SessionEntity;
use sinri\ledoc\entity\UserEntity;

class LeDocFilter extends ArkRequestFilter
{

    /**
     * Check request data with $_REQUEST, $_SESSION, $_SERVER, etc.
     * And decide if the request should be accepted.
     * If return false, the request would be thrown.
     * You can pass anything into $preparedData, that controller might use it (not sure, by the realization)
     * @param $path
     * @param $method
     * @param $params
     * @param mixed $preparedData
     * @param int $responseCode
     * @param null|string $error
     * @return bool
     */
    public function shouldAcceptRequest($path, $method, $params, &$preparedData = null, &$responseCode = 200, &$error = null)
    {
        $publicApiList = [
            "/api/SecurityController/login",
            "/api/UserController/registerUser",
            "/SiteController/",
            '/api/DocumentController/uploaded/',
            "/api/FolderController/getAllPublicFoldersAsTree",
            "/api/AnonymousReaderController/"
        ];
        if (self::hasPrefixAmong($path, $publicApiList)) return true;

        // Session & Privilege Controller
        $token = Ark()->webInput()->readRequest("token", '');
        $session = SessionEntity::verifyToken($token);
        if (!$session) {
            $responseCode = 403;
            $error = "Token Invalid";
            return false;
        }
        $user = UserEntity::loadUser($session->username);
        if (!$user || $user->status !== UserEntity::USER_STATUS_NORMAL) {
            $responseCode = 403;
            $error = "User Invalid";
            return false;
        }

        $preparedData['session'] = $session;
        $preparedData['user'] = $user;

        return true;
    }

    /**
     * Give filter a name for Error Report
     * @return string
     */
    public function filterTitle()
    {
        return "LeDoc Filter";
    }
}