<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/27
 * Time: 21:31
 */

namespace sinri\ledoc\core;


use sinri\ark\core\ArkHelper;
use sinri\ark\web\implement\ArkWebController;
use sinri\ledoc\entity\SessionEntity;
use sinri\ledoc\entity\UserEntity;

class LeDocBaseController extends ArkWebController
{
    /**
     * @var SessionEntity
     */
    protected $session;
    /**
     * @var UserEntity
     */
    protected $user;

    public function __construct()
    {
        parent::__construct();

        LeDoc::logger()->debug("controller got filterGeneratedData", ['as' => $this->filterGeneratedData]);

        $this->session = ArkHelper::readTarget($this->filterGeneratedData, 'session', new SessionEntity());
        $this->user = ArkHelper::readTarget($this->filterGeneratedData, 'user', new UserEntity());
    }

    /**
     * This method would be realized in Ark 1.1, here polyfill
     * @param string|array $name
     * @param null|mixed $default
     * @param null|string $regex
     * @param null|\Exception $error
     * @return mixed
     */
    protected function _readRequest($name, $default = null, $regex = null, &$error = null)
    {
        return Ark()->webInput()->readRequest($name, $default, $regex, $error);
    }

    /**
     * @param string|string[] $privileges
     * @throws \Exception
     */
    protected function _requirePrivileges($privileges)
    {
        if (!is_array($privileges)) $privileges = [$privileges];
        foreach ($privileges as $privilege)
            if (!$this->user->hasPrivilege(UserEntity::USER_PRIVILEGES_ADMIN)) {
                throw new \Exception("没有权限:" . $privilege);
            }
    }

}