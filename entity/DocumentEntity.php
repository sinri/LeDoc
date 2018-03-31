<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/28
 * Time: 15:20
 */

namespace sinri\ledoc\entity;


use sinri\ledoc\core\LeDocBaseEntity;
use sinri\ledoc\core\LeDocDataAgent;

/**
 * Class DocumentEntity
 * @package sinri\ledoc\entity
 * @property string $docHash
 * @property string $title
 * @property string[] $authors
 * @property string $content
 * @property string $type
 * @property-read array $history
 */
class DocumentEntity extends LeDocBaseEntity
{
    const DATA_TYPE_DOCUMENT = "folder";

    const DOCUMENT_TYPE_MARKDOWN = "markdown";

    /**
     * @var FolderEntity
     */
    protected $folder;

    public function __construct()
    {
        parent::__construct();

        $this->history = [];
    }

    /**
     * @param string $docHash
     * @param string[] $folderPathComponents
     * @return bool|DocumentEntity
     */
    public static function loadDocument(string $docHash, $folderPathComponents)
    {
        $folder = FolderEntity::loadFolderByPathComponents($folderPathComponents);
        if (!$folder) return false;
        $fileNames = $folder->getDocHashList();
        if (!in_array($docHash, $fileNames)) return false;
        $content = LeDocDataAgent::readRecordRawContent(self::DATA_TYPE_DOCUMENT, $docHash, $folderPathComponents);
        $json = json_decode($content, true);

        $doc = new DocumentEntity();
        $doc->loadPropertiesFromJson($json);

        $doc->folder = $folder;

        return $doc;
    }

    /**
     * @param FolderEntity $folder
     * @return bool|DocumentEntity
     */
    public static function prepareDocumentToCreate($folder)
    {
        $doc = new DocumentEntity();
        $doc->folder = $folder;
        $doc->docHash = uniqid(time());
        return $doc;
    }

    /**
     * @return string
     */
    public function getDocHash(): string
    {
        return $this->docHash;
    }

    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @param string $actUser
     * @param string $action
     * @param string $remark
     */
    public function appendHistory(string $actUser, string $action, string $remark)
    {
        $item = [
            "act_user" => $actUser,
            "action" => $action,
            "remark" => $remark,
            "date" => date('Y-m-d H:i:s'),
            "timestamp" => time(),
        ];
        $this->appendItemToArrayProperty('history', $item);
    }

    /**
     * @return string[]
     */
    public function propertyList()
    {
        return [
            "docHash",
            "title",
            "authors",
            "content",
            "type",
            "history",
        ];
    }

    public function save()
    {
        return LeDocDataAgent::writeRecordRawContent(json_encode($this->encodePropertiesForJson()), $this->getEntityDataType(), $this->docHash, $this->folder->getPathComponents());
    }

    public function suicide()
    {
        return LeDocDataAgent::removeRecord($this->getEntityDataType(), $this->docHash, $this->folder->getPathComponents());
    }

    /**
     * @return string
     */
    public function getEntityDataType()
    {
        return self::DATA_TYPE_DOCUMENT;
    }

    public function getDocumentInfo()
    {
        $info = $this->encodePropertiesForJson();
        $info['author_dict'] = [];
        foreach ($info['authors'] as $author) {
            $user = UserEntity::loadUser($author);
            $info['author_dict'][$author] = $user->encodePropertiesForJson();
        }
        return $info;
    }

    public function getShareKey($shareUsername)
    {
        $x = json_decode(json_encode($this->folder->getPathComponents()), true);
        array_unshift($x, $shareUsername);
        $x[] = $this->docHash;
        return json_encode($x);
    }
}