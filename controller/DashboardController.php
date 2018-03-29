<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/28
 * Time: 10:00
 */

namespace sinri\ledoc\controller;


use sinri\ledoc\core\LeDocBaseController;

class DashboardController extends LeDocBaseController
{
    public function getDashboardData()
    {
        try {
            // TODO get the folder (as root) list
            $folderList = $this->user->getUserRelatedFolders();

            $this->_sayOK(['folders' => $folderList]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}