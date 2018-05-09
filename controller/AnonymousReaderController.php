<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/5/9
 * Time: 23:46
 */

namespace sinri\ledoc\controller;


use sinri\ark\core\ArkHelper;
use sinri\ledoc\core\LeDocBaseController;
use sinri\ledoc\entity\DocumentEntity;
use sinri\ledoc\entity\FolderEntity;

class AnonymousReaderController extends LeDocBaseController
{
    public function getObjects()
    {
        try {
            $path_components = $this->_readRequest("root_path_components", []);

            $root_folder = FolderEntity::loadFolderByPathComponents($path_components);

            ArkHelper::assertItem($root_folder, "Incorrect root_path_components");

            $all_folder_list = $root_folder->getSubFolderPathComponents();

            $folder_list = [];
            foreach ($all_folder_list as $folder_path_components) {
                $folder = FolderEntity::loadFolderByPathComponents($folder_path_components);
                if (!$folder) continue;
                if (!$folder->isPublicReadable()) continue;
                $folder_list[] = [
                    "path_components" => $folder_path_components,
                    "folder_name" => $folder->getFolderName(),
                ];
            }

            $file_list = [];
            $docHashList = $root_folder->getDocHashList();
            foreach ($docHashList as $docHash) {
                $doc = DocumentEntity::loadDocument($docHash, $path_components);
                if (!$doc) continue;
                $file_list[] = $doc->encodePropertiesForJson();
            }

            $this->_sayOK([
                'folder_list' => $folder_list,
                'file_list' => $file_list,
                'current_folder' => $path_components
            ]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}