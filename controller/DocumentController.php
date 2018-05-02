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

    public function upload()
    {
        try {
            if (!file_exists(__DIR__ . '/../runtime/upload')) {
                mkdir(__DIR__ . '/../runtime/upload/', 0777, true);
            }
            $images = [];
            $file_keys = array_keys($_FILES);
            foreach ($file_keys as $file_key) {
                $this->_getInputHandler()->getUploadFileHelper()->handleUploadFileWithCallback(
                    $file_key,
                    function ($original_file_name, $file_type, $file_size, $file_tmp_name, $error) use (&$images) {
                        $hash_name = md5_file($file_tmp_name) . "." . $original_file_name;
                        $done = move_uploaded_file($file_tmp_name, __DIR__ . '/../runtime/upload/' . $hash_name);
                        if (!$done) return false;
                        $images[] = "../api/DocumentController/uploaded/" . $hash_name;
                        return true;
                    },
                    $uploadError
                );
            }
            $result = [
                // errno 即错误代码，0 表示没有错误。如果有错误，errno != 0，可通过下文中的监听函数 fail 拿到该错误码进行自定义处理
                "errno" => 0,
                // data 是一个数组，返回若干图片的线上地址
                "data" => $images,
            ];
        } catch (\Exception $exception) {
            $result = ['errno' => 1, 'data' => []];
        }
        $this->_getOutputHandler()->json($result);
    }

    public function uploaded($target)
    {
        try {
            $path = __DIR__ . '/../runtime/upload/' . $target;

            $path_parts = pathinfo($path);

            $mimes = new \Mimey\MimeTypes;
            // Convert extension to MIME type:
            $content_type = $mimes->getMimeType($path_parts['extension']); // application/json
            // Convert MIME type to extension:
            //$mimes->getExtension('application/json'); // json

            $this->_getOutputHandler()->downloadFileIndirectly($path, $content_type);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}