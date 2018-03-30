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
            ArkHelper::quickNotEmptyAssert("folderPathComponents error", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("folder error", $folder);

            ArkHelper::quickNotEmptyAssert("no permission", $folder->canUserEdit($this->user->username));

            $title = $this->_readRequest("title");
            $type = $this->_readRequest("type");
            $content = $this->_readRequest("content");
            ArkHelper::quickNotEmptyAssert("create document fields error", $title, $type);

            $doc = DocumentEntity::prepareDocumentToCreate($folder);
            $doc->title = $title;
            $doc->type = $type;
            $doc->content = $content;
            $doc->authors = [$this->user->username];
            $doc->appendHistory($this->user->username, "新建文档", $title);

            $done = $doc->save();
            ArkHelper::quickNotEmptyAssert("cannot save", $done);

            $this->_sayOK(['doc_hash' => $doc->docHash]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function loadDocument()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("folderPathComponents error", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("folder error", $folder);

            ArkHelper::quickNotEmptyAssert("no permission", $folder->canUserRead($this->user->username));

            $docHash = $this->_readRequest("doc_hash");
            $doc = DocumentEntity::loadDocument($docHash, $folderPathComponents);
            ArkHelper::quickNotEmptyAssert("document cannot be loaded", $doc);

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
            ArkHelper::quickNotEmptyAssert("folderPathComponents error", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("folder error", $folder);

            ArkHelper::quickNotEmptyAssert("no permission", $folder->canUserRead($this->user->username));

            $docHash = $this->_readRequest("doc_hash");
            $doc = DocumentEntity::loadDocument($docHash, $folderPathComponents);
            ArkHelper::quickNotEmptyAssert("document cannot be loaded", $doc);

            $title = $this->_readRequest("title");
            $content = $this->_readRequest("content");
            ArkHelper::quickNotEmptyAssert("create document fields error", $title, $content);

            $doc->title = $title;
            $doc->content = $content;
            $doc->appendItemToArrayProperty('authors', $this->user->username);
            $doc->authors = array_unique($doc->authors);
            $doc->appendHistory($this->user->username, "编辑文档", "");

            $done = $doc->save();
            ArkHelper::quickNotEmptyAssert("cannot save", $done);

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function removeDocument()
    {
        try {
            $folderPathComponents = $this->_readRequest("folder");
            ArkHelper::quickNotEmptyAssert("folderPathComponents error", $folderPathComponents, is_array($folderPathComponents));
            $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
            ArkHelper::quickNotEmptyAssert("folder error", $folder);

            ArkHelper::quickNotEmptyAssert("no permission", $folder->canUserEdit($this->user->username));

            $docHash = $this->_readRequest("doc_hash");
            $doc = DocumentEntity::loadDocument($docHash, $folderPathComponents);
            ArkHelper::quickNotEmptyAssert("document cannot be loaded", $doc);

            $done = $doc->suicide();
            ArkHelper::quickNotEmptyAssert("cannot remove", $done);

            $this->_sayOK();
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}