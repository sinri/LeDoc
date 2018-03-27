<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/27
 * Time: 10:39
 */

namespace sinri\ledoc\core;


class LeDocDataAgent
{
    /**
     * @param string $type Here type is limited to the defined constants inside the codes
     * @return string
     */
    protected static function getRecordDirectoryForType(string $type)
    {
        $dir = __DIR__ . '/../runtime/' . $type;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * @param string $type
     * @param string $key
     * @return string
     */
    public static function getRecordFilePath(string $type, string $key)
    {
        $dir = self::getRecordDirectoryForType($type);
        return $dir . '/' . base64_encode($key);
    }

    /**
     * @param string $type
     * @param string $key
     * @return false|string
     */
    public static function readRecordRawContent(string $type, string $key)
    {
        $path = self::getRecordFilePath($type, $key);
        if (!file_exists($path)) return false;
        else {
            $x = file_get_contents($path);
            return self::decrypt($x);
        }
    }

    /**
     * @param string $type
     * @param string $key
     * @param string $rawContent
     * @return bool|int
     */
    public static function writeRecordRawContent(string $type, string $key, string $rawContent)
    {
        $path = self::getRecordFilePath($type, $key);
        return file_put_contents($path, self::encrypt($rawContent));
    }

    /**
     * @param string $type
     * @param string $key
     * @return bool
     */
    public static function removeRecord(string $type, string $key)
    {
        $path = self::getRecordFilePath($type, $key);
        return unlink($path);
    }

    /**
     * @param string $type
     * @param string $pattern
     * @return string[]
     */
    public static function getRecordOriginalNamesWithGlob(string $type, string $pattern)
    {
        $fullPattern = self::getRecordFilePath($type, $pattern);
        $globList = glob($fullPattern);
        if (empty($globList)) return [];
        $list = [];
        $dir = self::getRecordDirectoryForType($type);
        $dirLen = strlen($dir) + 1;
        foreach ($globList as $item) {
            $list[] = base64_decode(substr($item, $dirLen));
        }
        return $list;
    }

    /**
     * @param string $content
     * @return string
     */
    public static function encrypt(string $content): string
    {
        return $content;
    }

    /**
     * @param string $content
     * @return string
     */
    public static function decrypt(string $content): string
    {
        return $content;
    }
}