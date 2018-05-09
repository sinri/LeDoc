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
    public function getAllPublicFoldersAsTree()
    {
        try {
            $root_folder = FolderEntity::loadFolderByPathComponents([]);
            $top_folder_list = $root_folder->getSubFolderPathComponents();

            $tree = [];

            foreach ($top_folder_list as $top_folder_path_components) {
                $folder = FolderEntity::loadFolderByPathComponents($top_folder_path_components);
                if (!$folder) continue;
                if (!$folder->isPublicReadable()) continue;
                $node = $this->buildTreeNode($top_folder_path_components, 1, '*');
                if ($node === false) continue;
                $tree[] = $node;
            }

            $this->_sayOK(['tree' => $tree]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function getMyFoldersAsTree()
    {
        try {
            $topFolders = $this->user->getUserRelatedFolders();

            $tree = [];
            for ($topFolderIndex = 0; $topFolderIndex < count($topFolders); $topFolderIndex++) {
                $node = $this->buildTreeNode($topFolders[$topFolderIndex], count($topFolders[$topFolderIndex]) - 1, $this->user->username);
                if ($node === false) continue;
                $tree[] = $node;
            }

            $this->_sayOK(['tree' => $tree]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    protected function buildTreeNode($pathComponents, int $basePathComponentsLength, string $username)
    {
        $folder = FolderEntity::loadFolderByPathComponents($pathComponents);
        if (!$folder) return false;

        $basePathComponents = json_decode(json_encode($pathComponents), true);
        $realPathComponents = array_splice($basePathComponents, $basePathComponentsLength);

        $children = [];

        $subFolders = $folder->getSubFolderPathComponents();
        foreach ($subFolders as $subFolder) {
            $children[] = $this->buildTreeNode($subFolder, $basePathComponentsLength, $username);
        }

        // for files
        $docHashList = $folder->getDocHashList();
        foreach ($docHashList as $docHash) {
            $doc = DocumentEntity::loadDocument($docHash, $pathComponents);
            $tree_node_key = json_decode(json_encode($pathComponents));
            $tree_node_key[] = $docHash;
            $tree_node_key = json_encode($tree_node_key);
            $children[] = [
                "doc_hash" => $docHash,
                "title" => $doc->title,
                "path_components" => $pathComponents,
                'base' => $basePathComponents,
                "tail" => $realPathComponents,
                "type" => "file",
                "expand" => (count($pathComponents) < 2),
                //"render"=>"treeNodeRender",
                "can_manage" => $folder->canUserManage($username),
                "can_edit" => $folder->canUserEdit($username),
                "can_read" => $folder->canUserRead($username),
                "tree_node_key" => $tree_node_key,
            ];
        }

        $tree_node_key = (json_encode($pathComponents));
        $node = [
            "title" => $folder->getFolderName(),
            "path_components" => $pathComponents,
            'base' => $basePathComponents,//for display
            "tail" => $realPathComponents,//for display
            "children" => $children,
            "type" => "dir",
            "expand" => (count($pathComponents) < 1),
            //"render"=>"treeNodeRender",
            "can_manage" => $folder->canUserManage($username),
            "can_edit" => $folder->canUserEdit($username),
            "can_read" => $folder->canUserRead($username),
            "tree_node_key" => $tree_node_key,
        ];

        return $node;
    }

    public function browseFolder()
    {
        try {
            $root = $this->_readRequest("root", []);
            ArkHelper::quickNotEmptyAssert("目录参数应为数组", is_array($root));
            $folder = FolderEntity::loadFolderByPathComponents($root);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);
            ArkHelper::quickNotEmptyAssert("无权访问此目录", $folder->isUserRelated($this->user->username));

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
            ArkHelper::quickNotEmptyAssert("新建目录参数不正常", is_array($parent), $folderName);

            LeDoc::logger()->debug(__METHOD__ . '@' . __LINE__, [
                "parent" => $parent, "folder_name" => $folderName, "current_user" => $this->user->encodePropertiesForJson()
            ]);

            $parentFolder = FolderEntity::loadFolderByPathComponents($parent);

            LeDoc::logger()->debug(__METHOD__ . '@' . __LINE__, ['parentFolder.path' => $parentFolder->getPathComponents()]);

            if (empty($parent)) {
                $done = $parentFolder->createSubFolder($folderName, $this->user->username);
            } else {
                $parentFolder->canUserEdit($this->user->username);
                $done = $parentFolder->createSubFolder($folderName, null);
            }
            ArkHelper::quickNotEmptyAssert("无法创建下级目录", $done);
            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function renameFolder()
    {
        try {
            $root = $this->_readRequest("folder", []);
            ArkHelper::quickNotEmptyAssert("目录参数应为数组", is_array($root));
            $folder = FolderEntity::loadFolderByPathComponents($root);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);
            ArkHelper::quickNotEmptyAssert("无权访问此目录", $folder->canUserEdit($this->user->username));

            $newName = $this->_readRequest("name");
            ArkHelper::quickNotEmptyAssert("新目录名不能为空", $newName);

            $done = $folder->renameTo($newName);
            ArkHelper::quickNotEmptyAssert("无法更名", $done);

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
            ArkHelper::quickNotEmptyAssert("目录参数应为数组", is_array($root));
            $folder = FolderEntity::loadFolderByPathComponents($root);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);
            ArkHelper::quickNotEmptyAssert("无权删除目录", $folder->canUserManage($this->user->username));

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
            ArkHelper::quickNotEmptyAssert("目录参数应为数组", is_array($root));
            $folder = FolderEntity::loadFolderByPathComponents($root);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);
            ArkHelper::quickNotEmptyAssert("无权访问目录", $folder->isUserRelated($this->user->username));

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

            $this->_sayOK(['result' => $result, 'is_public' => $folder->isPublicReadable()]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function removeFolderUserMapping()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("目录参数应为数组", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);

            $username = $this->_readRequest("username");
            if ($username !== '*') {
                $user = UserEntity::loadUser($username);
                ArkHelper::quickNotEmptyAssert("用户错误 " . $username, $user, $user->status === UserEntity::USER_STATUS_NORMAL);
                $folder->removePermissionOfUser($user->username);

                $folder->saveMeta();

                $user->spliceRelatedTopFolderPath($folder->getPathComponents());
                $user->saveUser();
            } else {
                $folder->removePermissionOfUser('*');
                $folder->saveMeta();
            }

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }

    }

    public function addFolderUserMapping()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("目录参数应为数组", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);

            $type = $this->_readRequest("type");
            ArkHelper::quickNotEmptyAssert("授权类型错误", in_array($type, ['reader', 'editor', 'manager']));

            $username_list = $this->_readRequest('username_list');
            ArkHelper::quickNotEmptyAssert("用户名列表应为数组", $username_list, is_array($username_list));

            foreach ($username_list as $username) {
                if ($username === '*' && $type === 'reader') {
                    // for reader all
                    $folder->permitUser($type, '*');
                } else {
                    $user = UserEntity::loadUser($username);
                    ArkHelper::quickNotEmptyAssert("用户出错 " . $username, $user, $user->status === UserEntity::USER_STATUS_NORMAL);

                    $folder->permitUser($type, $user->username);
                    $user->whenNewPermissionCreated($folder->getPathComponents());
                    $user->saveUser();
                }
            }
            $folder->saveMeta();

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

}