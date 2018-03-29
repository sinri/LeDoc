<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/28
 * Time: 15:20
 */

namespace sinri\ledoc\entity;


use sinri\ledoc\core\LeDocBaseEntity;

/**
 * Class FileEntity
 * @package sinri\ledoc\entity
 * @property string title
 * @property string[] authors
 * @property string content
 * @property string type
 */
class FileEntity extends LeDocBaseEntity
{

    /**
     * @return string
     */
    public function getEntityDataType()
    {
        return "file";
    }

    /**
     * @return string[]
     */
    public function propertyList()
    {
        return [
            "title",
            "authors",
            "content",
            "type",
        ];
    }
}