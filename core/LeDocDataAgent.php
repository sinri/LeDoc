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
     * @param string[] $folders
     * @return bool
     */
    public static function createFolder(string $type, $folders)
    {
        $dir = self::getRecordDirectoryForType($type);

        $path = $dir;

        foreach ($folders as $folder) {
            $path .= '/' . base64_encode($folder);
        }

        return mkdir($path, 0777, true);
    }

    /**
     * @param string $type
     * @param string[] $folders
     * @return bool
     */
    public static function removeFolder(string $type, $folders)
    {
        $dir = self::getRecordDirectoryForType($type);

        $path = $dir;

        foreach ($folders as $folder) {
            $path .= '/' . base64_encode($folder);
        }

        return self::recursiveRemoveDir($path);
    }

    /**
     * @param string $type
     * @param string[] $source
     * @param string[] $target
     * @return bool
     */
    public static function renameFolder(string $type, $source, $target)
    {
        $dir = self::getRecordDirectoryForType($type);

        $sourcePath = $dir;
        foreach ($source as $folder) {
            $sourcePath .= '/' . base64_encode($folder);
        }

        $targetPath = $dir;
        foreach ($target as $folder) {
            $targetPath .= '/' . base64_encode($folder);
        }

        return rename($sourcePath, $targetPath);
    }

    /**
     * @param string $type
     * @param string $key
     * @param string[] $folders empty array for no level in between, i.e. file at root folder
     * @return string
     */
    public static function getRecordFilePath(string $type, $key, $folders = [])
    {
        $dir = self::getRecordDirectoryForType($type);

        $path = $dir;

        foreach ($folders as $folder) {
            $path .= '/' . base64_encode($folder);
        }

        return $path . '/' . base64_encode($key);
    }

    /**
     * @param string $type
     * @param string[] $folders
     * @return string
     */
    public static function getFolderMetaPath(string $type, $folders = [])
    {
        $dir = self::getRecordDirectoryForType($type);
        $path = $dir;
        foreach ($folders as $folder) {
            $path .= '/' . base64_encode($folder);
        }
        return $path . '/' . ".LeDocFolderMeta";
    }

    /**
     * @param string $type
     * @param string[] $folders
     * @return false|string
     */
    public static function readFolderMeta(string $type, $folders = [])
    {
        $path = self::getFolderMetaPath($type, $folders);
        if (!file_exists($path)) return false;
        $x = file_get_contents($path);
        return self::decrypt($x);
    }

    /**
     * @param string $rawContent
     * @param string $type
     * @param array $folders
     * @return bool|int
     */
    public static function writeFolderMeta(string $rawContent, string $type, $folders = [])
    {
        $path = self::getFolderMetaPath($type, $folders);
        return file_put_contents($path, self::encrypt($rawContent));
    }

    /**
     * @param string $type
     * @param string $key
     * @param array $folders
     * @return false|string
     */
    public static function readRecordRawContent(string $type, string $key, $folders = [])
    {
        $path = self::getRecordFilePath($type, $key, $folders);
        if (!file_exists($path)) return false;
        $x = file_get_contents($path);
        return self::decrypt($x);
    }

    /**
     * @param string $rawContent
     * @param string $type
     * @param string $key
     * @param string[] $folders
     * @return bool|int
     */
    public static function writeRecordRawContent(string $rawContent, string $type, string $key, $folders = [])
    {
        $path = self::getRecordFilePath($type, $key, $folders);
        return file_put_contents($path, self::encrypt($rawContent));
    }

    /**
     * @param string $type
     * @param string $key
     * @param array $folders
     * @return bool
     */
    public static function removeRecord(string $type, string $key, $folders = [])
    {
        $path = self::getRecordFilePath($type, $key, $folders);
        return unlink($path);
    }

    /**
     * @param string $type
     * @param string $pattern
     * @param string[] $folders
     * @return string[]
     */
    public static function getRecordOriginalNamesWithGlob(string $type, string $pattern, $folders = [])
    {
        $dir = self::getRecordFilePath($type, "", $folders);
        $fullPattern = $dir . $pattern;
        $globList = glob($fullPattern);
        LeDoc::logger()->debug(__METHOD__ . '@' . __LINE__, [
            '$fullPattern' => $fullPattern,
            '$globList' => $globList,
        ]);
        if (empty($globList)) return [];
        $list = [];
        $dirLen = strlen($dir);
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

    public static function recursiveRemoveDir($src)
    {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    self::recursiveRemoveDir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        return rmdir($src);
    }

}