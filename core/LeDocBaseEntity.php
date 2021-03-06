<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/27
 * Time: 13:52
 */

namespace sinri\ledoc\core;


use sinri\ark\core\ArkHelper;

abstract class LeDocBaseEntity
{
    /**
     * @var array
     */
    private $properties;

    public function __construct()
    {
        $this->resetProperties();
    }

    public function __get($name)
    {
        return ArkHelper::readTarget($this->properties, $name);
    }

    public function __set($name, $value)
    {
        ArkHelper::writeIntoArray($this->properties, $name, $value);
    }

    public function __isset($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * @return string
     */
    abstract public function getEntityDataType();

    /**
     * @return string[]
     */
    abstract public function propertyList();

    public function resetProperties()
    {
        $this->properties = [];
    }

    /**
     * @return array
     */
    public function encodePropertiesForJson()
    {
        $json = [];
        foreach ($this->propertyList() as $property) {
            $json[$property] = $this->$property;
        }
        return $json;
    }

    /**
     * @param array $json
     */
    public function loadPropertiesFromJson($json)
    {
        foreach ($this->propertyList() as $property) {
            $this->$property = ArkHelper::readTarget($json, $property);
        }
    }

    /**
     * @param string $propertyName
     * @param $item
     */
    public function appendItemToArrayProperty(string $propertyName, $item)
    {
        if (!isset($this->properties[$propertyName]) || !is_array($this->properties[$propertyName])) {
            $this->properties[$propertyName] = [];
        }
        $this->properties[$propertyName][] = $item;
    }

    /**
     * @param string $propertyName
     * @param $item
     */
    public function removeItemInArrayProperty(string $propertyName, $item)
    {
        $index = array_search($item, $this->properties[$propertyName]);
        if ($index !== false) array_splice($this->properties[$propertyName], $index, 1);
    }
}