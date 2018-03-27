<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/26
 * Time: 23:29
 */

require_once __DIR__ . '/autoload.php';

date_default_timezone_set("Asia/Shanghai");

/**
 * @param string $directory __DIR__ . '/../controller'
 * @param string $urlBase "XX/"
 * @param string $controllerNamespaceBase '\sinri\sample\controller\\'
 * @param string $filters '\sinri\sample\filter\AuthFilter'
 */
Ark()->webService()->getRouter()->loadAllControllersInDirectoryAsCI(
    __DIR__ . '/controller',
    'api/',
    '\sinri\ledoc\controller\\',
    [
        \sinri\ledoc\core\LeDocFilter::class
    ]
);

Ark()->webService()->getRouter()->get("", function ($post, $comment) {
    header("Location: ./frontend/index.html");
});

Ark()->webService()->handleRequestForWeb();