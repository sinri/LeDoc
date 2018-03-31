<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/31
 * Time: 14:31
 */

namespace sinri\ledoc\entity;


use sinri\ledoc\core\LeDocBaseEntity;
use sinri\ledoc\core\LeDocDataAgent;

/**
 * Class ShareEntity
 * @package sinri\ledoc\entity
 * @property string $shareIndex
 * @property string[] $pathComponents
 * @property string $docHash
 * @property string $secret 分享密码，为空无效
 * @property int $expire 时间戳，在此之后分享无效。为0无效
 * @property string $owner 分享者
 */
class ShareEntity extends LeDocBaseEntity
{
    const DATA_TYPE_SHARE = "share";

    public function __construct()
    {
        parent::__construct();
        $this->pathComponents = [];
        $this->docHash = '';
        $this->secret = '';
        $this->expire = 0;
        $this->owner = "";
        $this->shareIndex = '';
    }

    /**
     * @return string
     */
    public function getEntityDataType()
    {
        return self::DATA_TYPE_SHARE;
    }

    /**
     * @return string[]
     */
    public function propertyList()
    {
        return [
            "shareIndex",
            "pathComponents",
            "docHash",
            "secret",
            "expire",
            "owner",
        ];
    }

    /**
     * @param string $shareIndex
     * @return bool|ShareEntity
     */
    public static function loadShareRecord(string $shareIndex)
    {
        $raw = LeDocDataAgent::readRecordRawContent(self::DATA_TYPE_SHARE, $shareIndex);
        if (!$raw) return false;
        $json = json_decode($raw, true);
        if (!$json) return false;

        $share = new ShareEntity();
        $share->loadPropertiesFromJson($json);
        return $share;
    }

    public function save()
    {
        $doc = $this->getDocument();
        if (!$doc) return false;
        return LeDocDataAgent::writeRecordRawContent(json_encode($this->encodePropertiesForJson()), $this->getEntityDataType(), $this->shareIndex);
    }

    /**
     * @return bool|DocumentEntity
     */
    public function getDocument()
    {
        $doc = DocumentEntity::loadDocument($this->docHash, $this->pathComponents);
        return $doc;
    }
}