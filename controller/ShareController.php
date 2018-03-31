<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/31
 * Time: 14:44
 */

namespace sinri\ledoc\controller;


use sinri\ark\core\ArkHelper;
use sinri\ledoc\core\LeDocBaseController;
use sinri\ledoc\entity\DocumentEntity;
use sinri\ledoc\entity\FolderEntity;
use sinri\ledoc\entity\ShareEntity;

class ShareController extends LeDocBaseController
{
    public function shareDocument()
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

            $expire = $this->_readRequest("expire", 0);
            $secret = $this->_readRequest('secret', '');

            $shareIndex = uniqid();
            $share = null;
            for ($i = 0; $i < 5; $i++) {
                $share = ShareEntity::loadShareRecord($shareIndex);
                if (!$share) break;
            }
            ArkHelper::quickNotEmptyAssert("cannot generate share index", !$share);

            $share = new ShareEntity();
            $share->shareIndex = $shareIndex;
            $share->pathComponents = $folder->getPathComponents();
            $share->docHash = $doc->docHash;
            $share->owner = $this->user->username;
            $share->expire = intval($expire, 10);
            $share->secret = $secret;

            $done = $share->save();
            ArkHelper::quickNotEmptyAssert("cannot share", $done);

            $this->_sayOK(["share_index" => $shareIndex]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function parseShareIndex()
    {
        try {
            $shareIndex = $this->_readRequest("share_index", '');

            $share = ShareEntity::loadShareRecord($shareIndex);
            ArkHelper::quickNotEmptyAssert("no such share", $share);

            if ($share->expire > 0 && $share->expire <= time()) {
                throw new \Exception("expired");
            }

            $this->_sayOK([
                "is_need_secret" => ($share->secret !== ''),
                "folder" => $share->pathComponents,
                "doc_hash" => $share->docHash,
                "owner" => $share->owner,
            ]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function getSharedDocument()
    {
        try {
            $shareIndex = $this->_readRequest("share_index", '');
            $secret = $this->_readRequest("secret", null);

            $share = ShareEntity::loadShareRecord($shareIndex);
            ArkHelper::quickNotEmptyAssert("no such share", $share);

            if ($share->expire > 0 && $share->expire <= time()) {
                throw new \Exception("expired");
            }
            if ($share->secret !== '' && $share->secret !== $secret) {
                throw new \Exception("auth fail");
            }

            $folder = FolderEntity::loadFolderByPathComponents($share->pathComponents);
            $doc = $share->getDocument();
            ArkHelper::quickNotEmptyAssert("no folder or doc", $folder, $doc);

            $this->_sayOK([
                "doc" => $doc->getDocumentInfo(),
                "folder" => $folder->getPathComponents(),
                "can_edit" => $folder->canUserEdit($this->user->username),
            ]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}