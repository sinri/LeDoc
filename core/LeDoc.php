<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/26
 * Time: 23:29
 */

namespace sinri\ledoc\core;


use Psr\Log\LogLevel;
use sinri\ark\core\ArkHelper;
use sinri\ark\core\ArkLogger;

class LeDoc
{
    /**
     * @param string|array $keyChain
     * @param $default
     * @return mixed
     */
    protected static function readConfig($keyChain, $default)
    {
        $config = [];
        require __DIR__ . '/../runtime/config.php';
        return ArkHelper::readTarget($config, $keyChain, $default);
    }

    /**
     * The session lifetime should be no less than 60 seconds.
     * @return int
     */
    public static function configOfSessionLifetime()
    {
        $x = self::readConfig(["session", "lifetime"], 3600 * 24 * 7);
        return max(intval($x, 10), 60);
    }

    protected static $logger;

    /**
     * @return ArkLogger
     */
    public static function logger()
    {
        if (!self::$logger) {
            self::$logger = new ArkLogger(__DIR__ . '/../runtime/log', 'LeDoc');
            self::$logger->setIgnoreLevel(LogLevel::DEBUG);//for debug
        }
        return self::$logger;
    }
}