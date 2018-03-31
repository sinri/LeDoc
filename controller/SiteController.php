<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/28
 * Time: 10:00
 */

namespace sinri\ledoc\controller;


use sinri\ledoc\core\LeDoc;
use sinri\ledoc\core\LeDocBaseController;

class SiteController extends LeDocBaseController
{
    public function getCopyrightInfo()
    {
        try {
            $copyright = LeDoc::configOfCopyright();
            $this->_sayOK(['copyright' => $copyright]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}