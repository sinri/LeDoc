<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/3/26
 * Time: 23:39
 */

namespace sinri\ledoc\controller;


use sinri\ark\web\implement\ArkWebController;

class SecurityController extends ArkWebController
{
    public function login()
    {
        try {
            // TODO act login
            $this->_sayOK(['...']);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}