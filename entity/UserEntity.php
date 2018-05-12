<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/27
 * Time: 10:36
 */

namespace sinri\ledoc\entity;


use sinri\ledoc\core\LeDocBaseEntity;
use sinri\ledoc\core\LeDocDataAgent;

/**
 * @property string username
 * @property string realname
 * @property string passwordHash
 * @property string status
 * @property string[] privileges
 * @property string[][] folders
 */
class UserEntity extends LeDocBaseEntity
{
    const DATA_TYPE_USER = 'user';

    const USER_STATUS_NORMAL = "NORMAL";
    const USER_STATUS_DISABLED = "DISABLED";

    const USER_PRIVILEGES_ADMIN = "PRIVILEGE_ADMIN";

    public function __construct()
    {
        parent::__construct();

        $this->folders = [];
        $this->privileges = [];
    }

    /**
     * @return string
     */
    public function getEntityDataType()
    {
        return self::DATA_TYPE_USER;
    }

    /**
     * @return string[]
     */
    public function propertyList()
    {
        return [
            "username",
            "realname",
            "passwordHash",
            "status",
            "privileges",
            "folders",
        ];
    }

    /**
     * @param string $username
     * @return bool|UserEntity
     */
    public static function loadUser($username)
    {
        $userEntity = new UserEntity();
        $raw = LeDocDataAgent::readRecordRawContent($userEntity->getEntityDataType(), $username);
        if ($raw === false) return false;
        $userEntity->loadPropertiesFromJson(json_decode($raw, true));
        return $userEntity;
    }

    /**
     * @param string $realPassword
     */
    public function setRealPassword(string $realPassword)
    {
        $this->passwordHash = password_hash($realPassword, PASSWORD_DEFAULT);
    }

    /**
     * @return bool|int
     */
    public function saveUser()
    {
        return LeDocDataAgent::writeRecordRawContent(json_encode($this->encodePropertiesForJson()), $this->getEntityDataType(), $this->username);
    }

    /**
     * @return string[][]
     */
    public function getUserRelatedFolders()
    {
        if (empty($this->folders) || !is_array($this->folders)) return [];
        $list = [];
        foreach ($this->folders as $folderComponents) {
            // $folderComponents should be an string[], try to find Meta
            $folder = FolderEntity::loadFolderByPathComponents($folderComponents);
            if (!$folder) continue;
            if (!$folder->isUserRelated($this->username)) continue;
            $list[] = $folderComponents;
        }
        //if(count($list)!==count($this->folders)) do some fix work
        usort($list, function ($a, $b) {
            $xa = implode('@', $a);
            $xb = implode('@', $b);
            return $xa > $xb;
        });
        return $list;
    }

    /**
     * @param string $privilege
     * @return bool
     */
    public function hasPrivilege(string $privilege)
    {
        return in_array($privilege, $this->privileges);
    }

    /**
     * @param string[] $folder
     */
    public function appendRelatedTopFolderPath($folder)
    {
        $this->appendItemToArrayProperty('folders', $folder);
    }

    /**
     * @param string[] $originPath
     * @param string|null $newName
     */
    public function spliceRelatedTopFolderPath($originPath, $newName = null)
    {
        $f = $this->folders;
        for ($i = 0; $i < count($f); $i++) {
            if (json_encode($f[$i]) === json_encode($originPath)) {
                if ($newName === null) {
                    unset($f[$i]); // 一气之下把整个folder删了
                } else {
                    $f[$i][count($f[$i]) - 1] = $newName; // 改个末端名字
                }
                break;
            }
        }
        $this->folders = $f;
    }

    /**
     * @return string[]
     */
    public static function getUserNameList()
    {
        $users = LeDocDataAgent::getRecordOriginalNamesWithGlob(self::DATA_TYPE_USER, '*');
        return $users;
    }

    public function whenNewPermissionCreated($pathComponents)
    {
        $folders = $this->getUserRelatedFolders();
        $newFolderHash = LeDocDataAgent::getRecordFilePath($this->getEntityDataType(), "", $pathComponents);
        $toBeReplaced = [];
        foreach ($folders as $key => $folder) {
            $folderHash = LeDocDataAgent::getRecordFilePath($this->getEntityDataType(), "", $folder);
            if (strpos($folderHash, $newFolderHash) === 0) {
                // 新的授权是在原有审批路径之下的，不需要新增顶级目录关联
                return;
            }
            if (strpos($newFolderHash, $folderHash) === 0) {
                // 新的授权包含了原有审批路径，需要炸掉原有的用新的替换
                $toBeReplaced[] = $key;
            }
        }
        foreach ($toBeReplaced as $key) {
            unset($folders[$key]);
        }
        $folders[] = $pathComponents;
        $this->folders = $folders;
    }
}