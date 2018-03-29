<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/28
 * Time: 09:20
 */

namespace sinri\ledoc\entity;


use sinri\ledoc\core\LeDoc;
use sinri\ledoc\core\LeDocBaseEntity;
use sinri\ledoc\core\LeDocDataAgent;

/**
 * Class FolderEntity
 * @package sinri\ledoc\entity
 * @property string[] managers
 * @property string[] editors
 * @property string[] readers
 */
class FolderEntity extends LeDocBaseEntity
{
    const DATA_TYPE_FOLDER = "folder";

    private $pathComponents;

    public function __construct()
    {
        parent::__construct();

        $this->managers = [];
        $this->editors = [];
        $this->readers = [];
    }

    /**
     * @return string[]
     */
    public function getPathComponents()
    {
        return $this->pathComponents;
    }

    /**
     * @param string[] $pathComponents
     */
    public function setPathComponents($pathComponents)
    {
        $this->pathComponents = $pathComponents;
    }

    /**
     * @return string
     */
    public function getEntityDataType()
    {
        return self::DATA_TYPE_FOLDER;
    }

    /**
     * @return string[]
     */
    public function propertyList()
    {
        return [
            "managers",
            "editors",
            "readers",
        ];
    }

    /**
     * @param string[] $folderComponents i.e. property $path
     * @return false|FolderEntity
     */
    public static function loadFolderByPathComponents($folderComponents)
    {
        if (empty($folderComponents)) {
            // as top
            $folder = new FolderEntity();
            $folder->pathComponents = [];
            return $folder;
        }

        $metaContent = LeDocDataAgent::readFolderMeta(FolderEntity::DATA_TYPE_FOLDER, $folderComponents);
        if (!$metaContent) return false;
        $meta = json_decode($metaContent, true);
        if (!is_array($meta)) return false;

        $folder = new FolderEntity();
        $folder->loadPropertiesFromJson($meta);
        $folder->pathComponents = $folderComponents;
        return $folder;
    }

    /**
     * @return string
     */
    public function getFolderName()
    {
        return $this->pathComponents[count($this->pathComponents) - 1];
    }

    /**
     * @param string $newName
     * @return bool
     */
    public function renameTo(string $newName)
    {
        $sourceFolder = $this->pathComponents;
        $targetFolder = json_decode(json_encode($sourceFolder), true);
        $targetFolder[count($this->pathComponents) - 1] = $newName;
        return LeDocDataAgent::renameFolder($this->getEntityDataType(), $sourceFolder, $targetFolder);
    }

    public function suicide()
    {
        return LeDocDataAgent::removeFolder($this->getEntityDataType(), $this->pathComponents);
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isUserRelated(string $username)
    {
        if (
            in_array($username, $this->managers)
            || in_array($username, $this->editors)
            || in_array($username, $this->readers)
        ) return true;
        if (count($this->pathComponents) > 1) {
            $p = json_decode(json_encode($this->pathComponents), true);
            array_splice($p, -1, 1);
            $parentFolder = FolderEntity::loadFolderByPathComponents($p);
            return $parentFolder->isUserRelated($username);
        } else {
            return false;
        }
    }

    /**
     * @param string $username
     * @return bool
     */
    public function canUserManage(string $username)
    {
        if (in_array($username, $this->managers)) return true;
        if (count($this->pathComponents) > 1) {
            $p = json_decode(json_encode($this->pathComponents), true);
            array_splice($p, -1, 1);
            $parentFolder = FolderEntity::loadFolderByPathComponents($p);
            return $parentFolder->canUserManage($username);
        } else {
            return false;
        }
    }

    /**
     * @param string $username
     * @return bool
     */
    public function canUserEdit(string $username)
    {
        if (in_array($username, $this->editors)) return true;
        if (count($this->pathComponents) > 1) {
            $p = json_decode(json_encode($this->pathComponents), true);
            array_splice($p, -1, 1);
            $parentFolder = FolderEntity::loadFolderByPathComponents($p);
            return $parentFolder->canUserEdit($username);
        } else {
            return false;
        }
    }

    /**
     * @param string $username
     * @return bool
     */
    public function canUserRead(string $username)
    {
        if (in_array($username, $this->readers)) return true;
        if (count($this->pathComponents) > 1) {
            $p = json_decode(json_encode($this->pathComponents), true);
            array_splice($p, -1, 1);
            $parentFolder = FolderEntity::loadFolderByPathComponents($p);
            return $parentFolder->canUserRead($username);
        } else {
            return false;
        }
    }

    /**
     * @param string $name
     * @param string|null $creator
     * @return bool
     */
    public function createSubFolder($name, $creator = null)
    {
        LeDoc::logger()->debug(__METHOD__ . '@' . __LINE__, ['name' => $name, "creator" => $creator]);
        $path = json_decode(json_encode($this->pathComponents), true);
        $path[] = $name;
        $created = LeDocDataAgent::createFolder($this->getEntityDataType(), $path);
        if (!$created) return false;

        $folder = new FolderEntity();
        $folder->managers = [];
        $folder->editors = [];
        $folder->readers = [];

        if ($creator !== null) {
            $folder->appendItemToArrayProperty('managers', $creator);
        }

        $written = LeDocDataAgent::writeFolderMeta(json_encode($folder->encodePropertiesForJson()), $this->getEntityDataType(), $path);
        if (!$written) {
            LeDocDataAgent::removeFolder($this->getEntityDataType(), $path);
            return false;
        }

        if ($creator !== null) {
            $user = UserEntity::loadUser($creator);
            if (!$user) {
                LeDocDataAgent::removeFolder($this->getEntityDataType(), $path);
                return false;
            }
            $user->appendRelatedTopFolderPath($path);
            if (!$user->saveUser()) {
                LeDocDataAgent::removeFolder($this->getEntityDataType(), $path);
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[][]
     */
    public function getSubFolderPathComponents()
    {
        $subFolderNames = LeDocDataAgent::getRecordOriginalNamesWithGlob($this->getEntityDataType(), '*', $this->pathComponents);
        if (empty($subFolderNames)) return [];
        $list = [];
        foreach ($subFolderNames as $folderName) {
            LeDoc::logger()->debug(__METHOD__ . '@' . __LINE__, ['raw_folder_name' => $folderName]);
            if (strpos($folderName, ".", 0)) continue;
            $dir = LeDocDataAgent::getRecordFilePath($this->getEntityDataType(), $folderName, $this->pathComponents);
            if (!file_exists($dir) || !is_dir($dir)) continue;
            $p = json_decode(json_encode($this->pathComponents), true);
            $p[] = $folderName;
            $list[] = $p;
        }
        LeDoc::logger()->debug(__METHOD__ . '@' . __LINE__, ['list' => $list]);
        return $list;
    }

    /**
     * @return string[]
     */
    public function getFileNames()
    {
        //TODO
        return [];
    }
}