<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/28
 * Time: 10:29
 */

namespace sinri\ledoc\controller;


use sinri\ark\core\ArkHelper;
use sinri\ledoc\core\LeDoc;
use sinri\ledoc\core\LeDocBaseController;
use sinri\ledoc\entity\DocumentEntity;
use sinri\ledoc\entity\FolderEntity;
use sinri\ledoc\entity\UserEntity;

class FolderController extends LeDocBaseController
{
    public function getAllFoldersAsTree()
    {
        // wonder if need this
    }

    public function getMyFoldersAsTree()
    {
        try {
            $topFolders = $this->user->getUserRelatedFolders();

            $tree = [];
            for ($topFolderIndex = 0; $topFolderIndex < count($topFolders); $topFolderIndex++) {
                $node = $this->buildTreeNode($topFolders[$topFolderIndex], count($topFolders[$topFolderIndex]) - 1);
                if ($node === false) continue;
                $tree[] = $node;
            }

            $this->_sayOK(['tree' => $tree]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    protected function buildTreeNode($pathComponents, int $basePathComponentsLength)
    {
        $folder = FolderEntity::loadFolderByPathComponents($pathComponents);
        if (!$folder) return false;

        $basePathComponents = json_decode(json_encode($pathComponents), true);
        $realPathComponents = array_splice($basePathComponents, $basePathComponentsLength);

        $children = [];

        $subFolders = $folder->getSubFolderPathComponents();
        foreach ($subFolders as $subFolder) {
            $children[] = $this->buildTreeNode($subFolder, $basePathComponentsLength);
        }

        // for files
        $docHashList = $folder->getDocHashList();
        foreach ($docHashList as $docHash) {
            $doc = DocumentEntity::loadDocument($docHash, $pathComponents);
            $children[] = [
                "doc_hash" => $docHash,
                "title" => $doc->title,
                "path_components" => $pathComponents,
                'base' => $basePathComponents,
                "tail" => $realPathComponents,
                "type" => "file",
                "expand" => true,
                //"render"=>"treeNodeRender",
                "can_manage" => $folder->canUserManage($this->user->username),
                "can_edit" => $folder->canUserEdit($this->user->username),
                "can_read" => $folder->canUserRead($this->user->username),
            ];
        }

        $node = [
            "title" => $folder->getFolderName(),
            "path_components" => $pathComponents,
            'base' => $basePathComponents,//for display
            "tail" => $realPathComponents,//for display
            "children" => $children,
            "type" => "dir",
            "expand" => true,
            //"render"=>"treeNodeRender",
            "can_manage" => $folder->canUserManage($this->user->username),
            "can_edit" => $folder->canUserEdit($this->user->username),
            "can_read" => $folder->canUserRead($this->user->username),
        ];

        return $node;
    }

    public function browseFolder()
    {
        try {
            $root = $this->_readRequest("root", []);
            ArkHelper::quickNotEmptyAssert("root should be array", is_array($root));
            $folder = FolderEntity::loadFolderByPathComponents($root);
            ArkHelper::quickNotEmptyAssert("not a folder", $folder);
            ArkHelper::quickNotEmptyAssert("not your folder", $folder->isUserRelated($this->user->username));

            $subFolderPathList = $folder->getSubFolderPathComponents();
            $fileList = $folder->getDocHashList();

            $this->_sayOK(['sub_folder_list' => $subFolderPathList, "file_list" => $fileList]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function createSubFolder()
    {
        try {
            $parent = $this->_readRequest("parent", []);
            $folderName = $this->_readRequest("name");
            ArkHelper::quickNotEmptyAssert("Input Error", is_array($parent), $folderName);

            LeDoc::logger()->debug(__METHOD__ . '@' . __LINE__, [
                "parent" => $parent, "folder_name" => $folderName, "current_user" => $this->user->encodePropertiesForJson()
            ]);

            $parentFolder = FolderEntity::loadFolderByPathComponents($parent);

            LeDoc::logger()->debug(__METHOD__ . '@' . __LINE__, ['parentFolder.path' => $parentFolder->getPathComponents()]);

            $done = $parentFolder->createSubFolder($folderName, (empty($parent) ? $this->user->username : null));
            ArkHelper::quickNotEmptyAssert("cannot create sub folder", $done);
            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function renameFolder()
    {
        try {
            $root = $this->_readRequest("folder", []);
            ArkHelper::quickNotEmptyAssert("folder should be array", is_array($root));
            $folder = FolderEntity::loadFolderByPathComponents($root);
            ArkHelper::quickNotEmptyAssert("not a folder", $folder);
            ArkHelper::quickNotEmptyAssert("not your folder", $folder->isUserRelated($this->user->username));

            $newName = $this->_readRequest("name");
            ArkHelper::quickNotEmptyAssert("new name should not be empty", $newName);

            $done = $folder->renameTo($newName);
            ArkHelper::quickNotEmptyAssert("cannot rename folder", $done);

            $this->user->spliceRelatedTopFolderPath($root, $newName);
            $this->user->saveUser();

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function removeFolder()
    {
        try {
            $root = $this->_readRequest("folder", []);
            ArkHelper::quickNotEmptyAssert("folder should be array", is_array($root));
            $folder = FolderEntity::loadFolderByPathComponents($root);
            ArkHelper::quickNotEmptyAssert("not a folder", $folder);
            ArkHelper::quickNotEmptyAssert("not your folder", $folder->isUserRelated($this->user->username));

            $dead = $folder->suicide();
            if ($dead) {
                $this->user->spliceRelatedTopFolderPath($root, null);
            }

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function getFolderRelatedUsers()
    {
        try {
            $root = $this->_readRequest("folder", []);
            ArkHelper::quickNotEmptyAssert("folder should be array", is_array($root));
            $folder = FolderEntity::loadFolderByPathComponents($root);
            ArkHelper::quickNotEmptyAssert("not a folder", $folder);
            ArkHelper::quickNotEmptyAssert("not your folder", $folder->isUserRelated($this->user->username));

            $result = $folder->getRelatedUsers();
            $result['by_path'] = array_reverse($result['by_path']);

            $result['manageable'] = false;
            foreach ($result['by_user'] as $user) {
                if ($user['username'] !== $this->user->username) continue;
                foreach ($user['permissions'] as $permission) {
                    if ($permission['type'] === "manager") {
                        $result['manageable'] = true;
                        break;
                    }
                }
            }

            $this->_sayOK(['result' => $result]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function removeFolderUserMapping()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("folderPathComponents error", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("folder error", $folder);

            $username = $this->_readRequest("username");
            $user = UserEntity::loadUser($username);
            ArkHelper::quickNotEmptyAssert("user error " . $username, $user, $user->status === UserEntity::USER_STATUS_NORMAL);

            $folder->removePermissionOfUser($user->username);
            $folder->saveMeta();

            $user->spliceRelatedTopFolderPath($folder->getPathComponents());
            $user->saveUser();

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }

    }

    public function addFolderUserMapping()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("folderPathComponents error", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("folder error", $folder);

            $type = $this->_readRequest("type");
            ArkHelper::quickNotEmptyAssert("type error", in_array($type, ['reader', 'editor', 'manager']));

            $username_list = $this->_readRequest('username_list');
            ArkHelper::quickNotEmptyAssert("username_list should be array", $username_list, is_array($username_list));

            foreach ($username_list as $username) {
                $user = UserEntity::loadUser($username);
                ArkHelper::quickNotEmptyAssert("user error " . $username, $user, $user->status === UserEntity::USER_STATUS_NORMAL);

                $folder->permitUser($type, $user->username);
                $user->whenNewPermissionCreated($folder->getPathComponents());
                $user->saveUser();
            }
            $folder->saveMeta();

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

}