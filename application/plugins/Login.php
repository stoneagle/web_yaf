<?php

/** 
 * 登陆插件类,LoginPlugin.php
 */
class LoginPlugin extends Yaf_Plugin_Abstract {

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        //$loginFlag = true;
        //$whiteList = array(
        //    "337545818"
        //);
        //if (!isset($data["opsKey"]) || !in_array($data["opsKey"],$whiteList)) {
        //    $session = Yaf_Session::getInstance();
        //    if (!$session['role']) {
        //        $request = Yaf_Dispatcher::getInstance()->getRequest();
        //        $request->setControllerName('User');
        //        $request->setActionName('login');
        //    }

        //    //如果用户不是管理员权限，则不允许访问用户模块
        //    $controller = $request->getControllerName();
        //    if (isset($session["role"]) && $session['role'] != 2 && $controller == "User") {
        //        echo "Permission denied";
        //        exit();
        //    }
        //}
    }
}
