<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/4/2
 * Time: 21:16
 */

namespace sinri\ledoc\controller;


use sinri\ledoc\core\LeDoc;
use sinri\ledoc\core\LeDocBaseController;
use sinri\ledoc\entity\UserEntity;

class ConfigController extends LeDocBaseController
{

    public function getConfigList()
    {
        try {
            $this->_requirePrivileges(UserEntity::USER_PRIVILEGES_ADMIN);
            $this->_sayOK([
                LeDoc::CONFIG_COPYRIGHT => LeDoc::configOfCopyright(),
                LeDoc::CONFIG_SESSION_LIFETIME => LeDoc::configOfSessionLifetime(),
            ]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function updateConfigList()
    {
        try {
            $this->_requirePrivileges(UserEntity::USER_PRIVILEGES_ADMIN);

            $config_list = $this->_readRequest("config_list", []);
            $done = LeDoc::updateConfigs($config_list);

            $this->_sayOK($done);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}