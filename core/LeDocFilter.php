<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/26
 * Time: 23:33
 */

namespace sinri\ledoc\core;


use sinri\ark\web\ArkRequestFilter;

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
            "/api/SecurityController/login"
        ];
        if (self::hasPrefixAmong($path, $publicApiList)) return true;

        // TODO Session & Privilege Controller

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