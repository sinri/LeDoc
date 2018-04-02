<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/30
 * Time: 13:58
 */

namespace sinri\ledoc\controller;


use sinri\ark\core\ArkHelper;
use sinri\ledoc\core\LeDocBaseController;
use sinri\ledoc\entity\DocumentEntity;
use sinri\ledoc\entity\FolderEntity;

class DocumentController extends LeDocBaseController
{
    public function createDocument()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("目录参数不正常", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);

            ArkHelper::quickNotEmptyAssert("没有权限", $folder->canUserEdit($this->user->username));

            $title = $this->_readRequest("title");
            $type = $this->_readRequest("type");
            $content = $this->_readRequest("content");
            ArkHelper::quickNotEmptyAssert("文档参数不正常", $title, $type);

            $doc = DocumentEntity::prepareDocumentToCreate($folder);
            $doc->title = $title;
            $doc->type = $type;
            $doc->content = $content;
            $doc->authors = [$this->user->username];
            $doc->appendHistory($this->user->username, "新建文档", $title);

            $done = $doc->save();
            ArkHelper::quickNotEmptyAssert("无法保存", $done);

            $this->_sayOK(['doc_hash' => $doc->docHash]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function loadDocument()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("目录参数不正常", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);

            ArkHelper::quickNotEmptyAssert("没有权限", $folder->canUserRead($this->user->username));

            $docHash = $this->_readRequest("doc_hash");
            $doc = DocumentEntity::loadDocument($docHash, $folderPathComponents);
            ArkHelper::quickNotEmptyAssert("无法加载文档", $doc);

            $this->_sayOK([
                "doc" => $doc->getDocumentInfo(),
                "folder" => $folder->getPathComponents(),
                "can_edit" => $folder->canUserEdit($this->user->username),
            ]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function updateDocument()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("目录参数不正常", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);

            ArkHelper::quickNotEmptyAssert("没有权限编辑", $folder->canUserRead($this->user->username));

            $docHash = $this->_readRequest("doc_hash");
            $doc = DocumentEntity::loadDocument($docHash, $folderPathComponents);
            ArkHelper::quickNotEmptyAssert("无法加载文档", $doc);

            $title = $this->_readRequest("title");
            $content = $this->_readRequest("content");
            ArkHelper::quickNotEmptyAssert("文档参数不正常", $title, $content);

            $doc->title = $title;
            $doc->content = $content;
            $doc->appendItemToArrayProperty('authors', $this->user->username);
            $doc->authors = array_unique($doc->authors);
            $doc->appendHistory($this->user->username, "编辑文档", "");

            $done = $doc->save();
            ArkHelper::quickNotEmptyAssert("无法保存", $done);

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function removeDocument()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("目录参数不正常", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("目录不正常", $folder);

            ArkHelper::quickNotEmptyAssert("没有权限删除文档", $folder->canUserEdit($this->user->username));

            $docHash = $this->_readRequest("doc_hash");
            $doc = DocumentEntity::loadDocument($docHash, $folderPathComponents);
            ArkHelper::quickNotEmptyAssert("无法加载文档", $doc);

            $done = $doc->suicide();
            ArkHelper::quickNotEmptyAssert("无法删除文档", $done);

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}