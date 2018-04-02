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
    const CONFIG_COPYRIGHT = "copyright";
    const CONFIG_SESSION_LIFETIME = "session_lifetime";

    /**
     * @param string|array $keyChain
     * @param $default
     * @return mixed
     */
    public static function readConfig($keyChain, $default)
    {
        $config = [];
        if (file_exists(__DIR__ . '/../runtime/config.php')) require __DIR__ . '/../runtime/config.php';
        return ArkHelper::readTarget($config, $keyChain, $default);
    }

    /**
     * @param string $key
     * @param $content
     * @return bool|int
     */
    public static function updateConfig($key, $content)
    {
        $config = [];
        if (file_exists(__DIR__ . '/../runtime/config.php')) require __DIR__ . '/../runtime/config.php';

        $config[$key] = $content;

        ob_start();
        var_export($config);
        ob_end_clean();
        $ve = ob_get_contents();

        return file_put_contents(
            __DIR__ . '/../runtime/config.php',
            '<?php' . PHP_EOL .
            '$config=' . $ve . ';' . PHP_EOL
        );
    }

    /**
     * @param array $array
     * @return bool|int
     */
    public static function updateConfigs($array)
    {
        $config = [];
        if (file_exists(__DIR__ . '/../runtime/config.php')) require __DIR__ . '/../runtime/config.php';

        foreach ($array as $key => $value) $config[$key] = $value;

        ob_start();
        var_export($config);
        ob_end_clean();
        $ve = ob_get_contents();

        return file_put_contents(
            __DIR__ . '/../runtime/config.php',
            '<?php' . PHP_EOL .
            '$config=' . $ve . ';' . PHP_EOL
        );
    }

    /**
     * The session lifetime should be no less than 60 seconds.
     * @return int
     */
    public static function configOfSessionLifetime()
    {
        $x = self::readConfig(self::CONFIG_SESSION_LIFETIME, 3600 * 24 * 7);
        return max(intval($x, 10), 60);
    }

    public static function configOfCopyright()
    {
        return self::readConfig([self::CONFIG_COPYRIGHT], "Sinri Edogawa 及相关文档作者对文档内容保留所有权利，未经许可不得进行转载变更等行径。");
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