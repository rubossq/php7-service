<?php
namespace Famous\Lib\Managers;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Utils\Constant as Constant;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/25/2016
 * Time: 12:19
 */
class SessionManager
{
    public static function prepare($user){

        self::prepareSessionAuth($user);
        $data = new Response("prepare", Constant::OK_STATUS);

        return $data;
    }

    private function prepareSessionAuth($user){
        self::updateUser($user);
    }

    public static function updateUser($user){
        $_SESSION['user'] = serialize($user);
        Manager::init();
    }
}