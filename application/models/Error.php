<?php
class ErrorModel
{
    const ERR_OK                = 0;
    const ERR_REST_ACTION_ERROR = 0001;
    const ERR_LOGIN_FAIL        = 1000;

    public static $err_info  = array(
        self::ERR_OK                => "操作成功",
        self::ERR_REST_ACTION_ERROR => "方法不存在",
        self::ERR_LOGIN_FAIL        => "登录失败",
    );

    public static function getMsg($err_no)
    {
        return self::$err_info[$err_no];
    }
}
