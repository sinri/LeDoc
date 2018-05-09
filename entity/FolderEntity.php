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

    public function canUserRead(string $username)
    {
        return $this->isUserRelated($username);
    }

    public function canUserEdit(string $username)
    {
        return $this->isUserEditor($username) || $this->isUserManager($username);
    }

    public function canUserManage(string $username)
    {
        return $this->isUserManager($username);
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isUserRelated(string $username)
    {
        if (in_array('*', $this->readers)) {
            return true;
        }
        if (
            in_array($username, $this->managers)
            || in_array($username, $this->editors)
            || in_array($username, $this->readers)
        ) {
            return true;
        }
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
    public function isUserManager(string $username)
    {
        if (in_array($username, $this->managers)) return true;
        if (count($this->pathComponents) > 1) {
            $p = json_decode(json_encode($this->pathComponents), true);
            array_splice($p, -1, 1);
            $parentFolder = FolderEntity::loadFolderByPathComponents($p);
            return $parentFolder->isUserManager($username);
        } else {
            return false;
        }
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isUserEditor(string $username)
    {
        if (in_array($username, $this->editors)) return true;
        if (count($this->pathComponents) > 1) {
            $p = json_decode(json_encode($this->pathComponents), true);
            array_splice($p, -1, 1);
            $parentFolder = FolderEntity::loadFolderByPathComponents($p);
            return $parentFolder->isUserEditor($username);
        } else {
            return false;
        }
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isUserReader(string $username)
    {
        if (in_array($username, $this->readers)) return true;
        if (count($this->pathComponents) > 1) {
            $p = json_decode(json_encode($this->pathComponents), true);
            array_splice($p, -1, 1);
            $parentFolder = FolderEntity::loadFolderByPathComponents($p);
            return $parentFolder->isUserReader($username);
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isPublicReadable()
    {
        if (in_array('*', $this->readers)) return true;
        if (count($this->pathComponents) > 1) {
            $p = json_decode(json_encode($this->pathComponents), true);
            array_splice($p, -1, 1);
            $parentFolder = FolderEntity::loadFolderByPathComponents($p);
            return $parentFolder->isPublicReadable();
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
    public function getDocHashList()
    {
        $fileNames = LeDocDataAgent::getRecordOriginalNamesWithGlob($this->getEntityDataType(), '*', $this->pathComponents);
        if (empty($fileNames)) return [];
        $list = [];
        foreach ($fileNames as $fileName) {
            if (strpos($fileName, ".", 0)) continue;
            $path = LeDocDataAgent::getRecordFilePath($this->getEntityDataType(), $fileName, $this->pathComponents);
            if (!file_exists($path) || !is_file($path)) continue;
            $list[] = $fileName;
        }
        LeDoc::logger()->debug(__METHOD__ . '@' . __LINE__, ['list' => $list]);
        return $list;
    }

    /**
     * @return array
     */
    public function getRelatedUsers()
    {
        $pathPermissionList = [];
        $currentFolderHash = json_encode($this->pathComponents);
        $pathComponents = json_decode(json_encode($this->pathComponents), true);

        $pathPermissionList[] = [
            "path_components" => $pathComponents,
            "hash" => json_encode($pathComponents),
            "readers" => $this->readers,
            "editors" => $this->editors,
            "managers" => $this->managers,
        ];

        while (count($pathComponents) > 1) {
            array_splice($pathComponents, -1, 1);
            $folder = FolderEntity::loadFolderByPathComponents($pathComponents);
            $pathPermissionList[] = [
                "path_components" => $pathComponents,
                "hash" => json_encode($pathComponents),
                "readers" => $folder->readers,
                "editors" => $folder->editors,
                "managers" => $folder->managers,
            ];
        }

        $userPermissionList = [];
        foreach ($pathPermissionList as $pathPermission) {
            foreach (['manager', 'reader', 'editor'] as $type) {
                foreach ($pathPermission[$type . 's'] as $username) {
                    $user = UserEntity::loadUser($username);
                    if (!$user || $user->status !== UserEntity::USER_STATUS_NORMAL) continue;
                    if (!isset($userPermissionList[$username])) $userPermissionList[$username] = [
                        "username" => $username,
                        "realname" => $user->realname,
                        "permissions" => [],
                    ];
                    $hash = json_encode($pathPermission['path_components']);
                    $userPermissionList[$username]['permissions'][] = [
                        "type" => $type,
                        "path_components" => $pathPermission['path_components'],
                        "hash" => $hash,
                        "is_direct_permission" => ($currentFolderHash === $hash),
                    ];
                }
            }
        }
        $userPermissionList = array_values($userPermissionList);

        return ["by_path" => $pathPermissionList, "by_user" => $userPermissionList];
    }

    /**
     * @param string $type
     * @param string $username
     */
    public function permitUser(string $type, string $username)
    {
        $x = $this->getRelatedUsers();
        $x = $x['by_user'];
        $canPermitOnThisFolder = false;
        if (isset($x[$username])) {
            $permissions = $x[$username]['permissions'];
            for ($y = 0; $y < count($permissions); $y++) {
                $permission = $permissions[$y];
                if ($permission['is_direct_permission']) {
                    $canPermitOnThisFolder = true;
                    break;
                }
            }
        } else {
            $canPermitOnThisFolder = true;
        }
        if (!$canPermitOnThisFolder) return;
        $this->removeItemInArrayProperty('managers', $username);
        $this->removeItemInArrayProperty('editors', $username);
        $this->removeItemInArrayProperty('readers', $username);

        $this->appendItemToArrayProperty($type . 's', $username);
    }

    /**
     * @param string $username
     */
    public function removePermissionOfUser(string $username)
    {
        $this->removeItemInArrayProperty('managers', $username);
        $this->removeItemInArrayProperty('editors', $username);
        $this->removeItemInArrayProperty('readers', $username);
    }

    public function saveMeta()
    {
        return LeDocDataAgent::writeFolderMeta(json_encode($this->encodePropertiesForJson()), $this->getEntityDataType(), $this->getPathComponents());
    }
}